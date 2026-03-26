"""
RatingService — Python port of app/Services/RatingService.php

Key improvements over PHP version:
- Trie-based prefix matching (O(n) vs SQL query per lookup)
- In-memory rate cache with TTL (reduces DB load)
- Decimal precision matches PHP bcmath
"""

import json
import math
import logging
import re
import time
from datetime import date, datetime
from decimal import Decimal
from typing import Optional
from dataclasses import dataclass

import redis as redis_lib
from sqlalchemy import and_, or_
from sqlalchemy.orm import Session

from billing.trie import PrefixTrie
from billing.exceptions import RateNotFoundException
from shared.models.rate import Rate
from shared.models.rate_group import RateGroup
from shared.models.call_record import CallRecord
from shared.models.user import User

logger = logging.getLogger(__name__)


@dataclass
class CostResult:
    """Result of cost calculation for a call."""
    billable_duration: int
    total_cost: Decimal


@dataclass
class ResolvedRates:
    """Sell rate (user's group), optional cost rate (reseller), optional trunk rate (provider)."""
    sell: Rate
    cost: Optional[Rate]
    trunk: Optional[Rate] = None


class RatingService:
    """
    Rates call records by finding the longest-prefix-match rate
    and calculating cost using billing increments.

    Equivalent to PHP RatingService with these methods:
    - findRate()      → find_rate()
    - resolveRates()  → resolve_rates()
    - calculateCost() → calculate_cost()
    - rateCall()      → rate_call()
    """

    def __init__(self, redis_client: redis_lib.Redis):
        self.redis = redis_client
        # In-memory trie cache: key = "group_id:date" → (trie, loaded_at)
        self._trie_cache: dict[str, tuple[PrefixTrie, float]] = {}
        self._trie_ttl = 300  # 5 minutes

    # ─────────────────────────────────────────────────────
    # find_rate — Trie-based longest-prefix-match
    # PHP equivalent: RatingService::findRate()
    # ─────────────────────────────────────────────────────

    def find_rate(
        self,
        destination: str,
        rate_group_id: int,
        at_time: Optional[date] = None,
        session: Optional[Session] = None,
    ) -> Optional[Rate]:
        """
        Find the best matching rate via longest-prefix-match.

        PHP version uses SQL: WHERE prefix IN (...) ORDER BY LENGTH(prefix) DESC
        Python version uses Trie: O(n) lookup where n = digits in number
        """
        at_time = at_time or date.today()
        clean_number = re.sub(r"\D", "", destination)

        if not clean_number:
            return None

        # Check Redis cache first (same key format as PHP)
        cache_prefix = clean_number[:10]
        cache_key = f"rate:{rate_group_id}:{cache_prefix}:{at_time}"

        cached = self.redis.get(cache_key)
        if cached:
            data = json.loads(cached)
            logger.debug(f"find_rate: Redis cache hit for {cache_key}")
            return self._dict_to_rate(data)

        # Get or build trie for this rate group
        trie = self._get_trie(rate_group_id, at_time, session)
        rate = trie.longest_match(clean_number)

        logger.info(
            f"find_rate: group={rate_group_id}, number={clean_number}, "
            f"trie_size={len(trie)}, match={'prefix=' + rate.prefix if rate else 'none'}"
        )

        # Cache result in Redis (same TTL as PHP: 300s)
        if rate:
            self.redis.setex(cache_key, 300, json.dumps(rate.to_cache_dict()))

        return rate

    def _get_trie(
        self,
        rate_group_id: int,
        at_time: date,
        session: Optional[Session] = None,
    ) -> PrefixTrie:
        """
        Load all rates for a group into a Trie structure.
        Cached in memory with TTL — rebuilt when expired or on rate change.
        """
        cache_key = f"{rate_group_id}:{at_time}"
        now = time.time()

        # Check memory cache
        if cache_key in self._trie_cache:
            trie, loaded_at = self._trie_cache[cache_key]
            if now - loaded_at < self._trie_ttl:
                return trie

        # Build new trie from database
        trie = PrefixTrie()

        if session is None:
            from shared.database import get_session
            with get_session() as s:
                self._load_rates_into_trie(s, trie, rate_group_id, at_time)
        else:
            self._load_rates_into_trie(session, trie, rate_group_id, at_time)

        self._trie_cache[cache_key] = (trie, now)
        logger.info(
            f"Built trie for rate_group {rate_group_id}: {len(trie)} rates loaded"
        )
        return trie

    def _load_rates_into_trie(
        self,
        session: Session,
        trie: PrefixTrie,
        rate_group_id: int,
        at_time: date,
    ) -> None:
        """Load active rates from DB into the trie as detached dicts."""
        rates = (
            session.query(Rate)
            .filter(
                Rate.rate_group_id == rate_group_id,
                Rate.status == "active",
                Rate.effective_date <= at_time,
                or_(
                    Rate.end_date.is_(None),
                    Rate.end_date > at_time,
                ),
            )
            .order_by(Rate.effective_date.desc())
            .all()
        )

        # Insert into trie — first match per prefix wins (most recent effective_date)
        # Eagerly convert to detached Rate objects so they work outside the session
        seen_prefixes = set()
        for rate in rates:
            if rate.prefix not in seen_prefixes:
                detached = self._detach_rate(rate)
                trie.insert(detached.prefix, detached)
                seen_prefixes.add(detached.prefix)

    @staticmethod
    def _detach_rate(rate: Rate) -> Rate:
        """Create a detached Rate with all attributes eagerly loaded."""
        r = Rate()
        r.id = rate.id
        r.rate_group_id = rate.rate_group_id
        r.prefix = rate.prefix
        r.destination = rate.destination
        r.rate_per_minute = rate.rate_per_minute
        r.connection_fee = rate.connection_fee
        r.min_duration = rate.min_duration
        r.billing_increment = rate.billing_increment
        r.effective_date = rate.effective_date
        r.end_date = rate.end_date
        r.status = rate.status
        return r

    # ─────────────────────────────────────────────────────
    # resolve_rates — sell rate + cost rate
    # PHP equivalent: RatingService::resolveRates()
    # ─────────────────────────────────────────────────────

    def resolve_rates(
        self,
        destination: str,
        rate_group_id: int,
        reseller_id: Optional[int] = None,
        trunk_id: Optional[int] = None,
        at_time: Optional[date] = None,
        session: Optional[Session] = None,
    ) -> ResolvedRates:
        """
        Resolve sell rate (user's group), cost rate (reseller's group),
        and trunk rate (provider's group).

        Cost rate uses reseller_id from CDR (captured at call time).
        Trunk rate uses outgoing_trunk_id from CDR.
        """
        at_time = at_time or date.today()

        sell_rate = self.find_rate(destination, rate_group_id, at_time, session)
        if not sell_rate:
            raise RateNotFoundException(destination, rate_group_id)

        cost_rate = None
        trunk_rate = None

        def _resolve(sess):
            nonlocal cost_rate, trunk_rate
            if reseller_id:
                cost_rate = self._find_cost_rate(sess, destination, reseller_id, at_time)
            if trunk_id:
                trunk_rate = self._find_trunk_rate(sess, destination, trunk_id, at_time)

        if session is None:
            from shared.database import get_session
            with get_session() as s:
                _resolve(s)
        else:
            _resolve(session)

        return ResolvedRates(sell=sell_rate, cost=cost_rate, trunk=trunk_rate)

    def _find_cost_rate(
        self,
        session: Session,
        destination: str,
        reseller_id: int,
        at_time: date,
    ) -> Optional[Rate]:
        """
        Find cost rate from reseller's rate group.
        Uses reseller_id from CDR (captured at call time).
        """
        reseller = session.query(User).get(reseller_id)
        if not reseller:
            return None
        # Parent is super_admin → direct client → no reseller cost
        if reseller.role == 'super_admin':
            return None
        # Parent is reseller → use reseller's rate group
        if reseller.role == 'reseller' and reseller.rate_group_id:
            return self.find_rate(destination, reseller.rate_group_id, at_time, session)
        return None

    def _find_trunk_rate(
        self,
        session: Session,
        destination: str,
        trunk_id: int,
        at_time: date,
    ) -> Optional[Rate]:
        """
        Find trunk rate from the outgoing trunk's rate group (provider's rate card).
        Used to calculate platform's actual cost per call.
        """
        from sqlalchemy import text as sa_text
        row = session.execute(
            sa_text("SELECT rate_group_id FROM trunks WHERE id = :tid"),
            {"tid": trunk_id},
        ).first()
        if row and row.rate_group_id:
            return self.find_rate(destination, row.rate_group_id, at_time, session)
        return None

    def _rate_transit_call(self, session: Session, cdr, destination: str) -> dict:
        """
        Rate a trunk-to-trunk transit call.

        Revenue = incoming trunk's rate group (what provider pays platform)
        Cost = outgoing trunk's rate group (what platform pays provider)
        No user balance involved — transit is invoice-based.
        """
        at_time = cdr.call_start.date() if cdr.call_start else date.today()
        call_record_id = cdr.id

        # Find incoming trunk rate (revenue)
        incoming_rate = None
        if cdr.incoming_trunk_id:
            incoming_rate = self._find_trunk_rate(session, destination, cdr.incoming_trunk_id, at_time)

        # Find outgoing trunk rate (cost)
        outgoing_rate = None
        if cdr.outgoing_trunk_id:
            outgoing_rate = self._find_trunk_rate(session, destination, cdr.outgoing_trunk_id, at_time)

        if not incoming_rate and not outgoing_rate:
            logger.info(f"rate_call: transit no rates [cdr={call_record_id}]")
            cdr.status = "unbillable"
            cdr.rated_at = datetime.now()
            session.commit()
            return {"status": "unbillable", "reason": "no_trunk_rate", "call_flow": "trunk_to_trunk"}

        # Calculate revenue (incoming trunk rate)
        if incoming_rate:
            revenue = self.calculate_cost(cdr.billsec, incoming_rate)
            cdr.total_cost = revenue.total_cost
            cdr.matched_prefix = incoming_rate.prefix
            cdr.rate_per_minute = incoming_rate.rate_per_minute
            cdr.connection_fee = incoming_rate.connection_fee
            cdr.billable_duration = revenue.billable_duration
        else:
            cdr.total_cost = Decimal("0.0000")
            cdr.billable_duration = cdr.billsec

        # Calculate cost (outgoing trunk rate)
        if outgoing_rate:
            cost = self.calculate_cost(cdr.billsec, outgoing_rate)
            cdr.trunk_cost = cost.total_cost
        else:
            cdr.trunk_cost = Decimal("0.0000")

        # No reseller involved in transit
        cdr.reseller_cost = Decimal("0.0000")

        cdr.status = "rated"
        cdr.rated_at = datetime.now()
        session.commit()

        logger.info(
            f"rate_call: transit rated [cdr={call_record_id}, "
            f"revenue={cdr.total_cost}, trunk_cost={cdr.trunk_cost}]"
        )

        return {
            "status": "rated",
            "call_record_id": call_record_id,
            "call_flow": "trunk_to_trunk",
            "matched_prefix": cdr.matched_prefix or "",
            "total_cost": str(cdr.total_cost),
            "reseller_cost": "0.0000",
            "trunk_cost": str(cdr.trunk_cost),
            "billable_duration": cdr.billable_duration,
        }

    # ─────────────────────────────────────────────────────
    # calculate_cost — Decimal precision (matches PHP bcmath)
    # PHP equivalent: RatingService::calculateCost()
    # ─────────────────────────────────────────────────────

    @staticmethod
    def calculate_cost(billsec: int, rate: Rate) -> CostResult:
        """
        Calculate the total cost for a call.

        PHP uses bcmath (bcdiv, bcmul, bcadd) with string precision.
        Python uses Decimal for equivalent precision.

        Formula: (billable_duration / 60) * rate_per_minute + connection_fee
        """
        min_duration = int(rate.min_duration or 0)
        billing_increment = max(1, int(rate.billing_increment or 1))

        # Apply minimum duration
        effective_duration = max(billsec, min_duration)

        # Round up to billing increment
        billable_duration = math.ceil(
            effective_duration / billing_increment
        ) * billing_increment

        # Calculate cost with Decimal precision (matches PHP bcmath 4-digit scale)
        duration_minutes = Decimal(str(billable_duration)) / Decimal("60")
        usage_cost = duration_minutes * Decimal(str(rate.rate_per_minute))
        total_cost = (
            usage_cost + Decimal(str(rate.connection_fee or 0))
        ).quantize(Decimal("0.0001"))

        return CostResult(
            billable_duration=billable_duration,
            total_cost=total_cost,
        )

    # ─────────────────────────────────────────────────────
    # rate_call — Rate a single CDR
    # PHP equivalent: RatingService::rateCall()
    # ─────────────────────────────────────────────────────

    def rate_call(self, call_record_id: int) -> dict:
        """
        Rate a single call record: find rates, calculate costs, update CDR.

        Returns dict with status and details.
        """
        from shared.database import get_session

        with get_session() as session:
            cdr = session.query(CallRecord).get(call_record_id)

            if not cdr:
                raise ValueError(f"CallRecord {call_record_id} not found")

            # Idempotency: skip if already rated (prevents re-rating on Celery retry)
            if cdr.status == "rated" and cdr.rated_at:
                logger.info(
                    f"rate_call: SKIPPED — already rated "
                    f"[cdr={call_record_id}, cost={cdr.total_cost}]"
                )
                return {
                    "status": "rated",
                    "call_record_id": call_record_id,
                    "matched_prefix": cdr.matched_prefix,
                    "total_cost": str(cdr.total_cost),
                    "reseller_cost": str(cdr.reseller_cost),
                    "trunk_cost": str(cdr.trunk_cost),
                    "billable_duration": cdr.billable_duration,
                }

            # Use destination if it looks like a phone number, otherwise use callee
            raw_dest = cdr.destination or ""
            destination = raw_dest if any(c.isdigit() for c in raw_dest) else cdr.callee

            # ── Transit calls (trunk_to_trunk): no user, use trunk rates ──
            if cdr.call_flow == "trunk_to_trunk":
                return self._rate_transit_call(session, cdr, destination)

            # ── P2P calls (sip_to_sip): internal, no billing ──
            if cdr.call_flow == "sip_to_sip":
                cdr.status = "rated"
                cdr.total_cost = Decimal("0.0000")
                cdr.reseller_cost = Decimal("0.0000")
                cdr.trunk_cost = Decimal("0.0000")
                cdr.rated_at = datetime.now()
                session.commit()
                return {
                    "status": "rated",
                    "call_record_id": cdr.id,
                    "total_cost": "0.0000",
                    "reseller_cost": "0.0000",
                    "trunk_cost": "0.0000",
                    "billable_duration": cdr.billsec,
                }

            user = session.query(User).get(cdr.user_id)

            # No user or rate group → unbillable (same as PHP)
            if not user or not user.rate_group_id:
                logger.warning(
                    f"rate_call: user or rate_group_id missing "
                    f"[cdr={call_record_id}, user_id={cdr.user_id}]"
                )
                cdr.status = "unbillable"
                cdr.rated_at = datetime.now()
                session.commit()
                return {"status": "unbillable", "reason": "no_rate_group"}

            # Find sell + cost + trunk rates
            # Uses cdr.reseller_id and cdr.outgoing_trunk_id (captured at call time)
            # Use outgoing_trunk_id for outbound, incoming_trunk_id for inbound
            trunk_id_for_cost = cdr.outgoing_trunk_id or cdr.incoming_trunk_id

            try:
                rates = self.resolve_rates(
                    destination,
                    user.rate_group_id,
                    reseller_id=cdr.reseller_id,
                    trunk_id=trunk_id_for_cost,
                    at_time=cdr.call_start.date() if cdr.call_start else date.today(),
                    session=session,
                )
            except RateNotFoundException:
                logger.info(
                    f"rate_call: no rate found "
                    f"[cdr={call_record_id}, dest={destination}, "
                    f"group={user.rate_group_id}]"
                )
                cdr.status = "unbillable"
                cdr.rated_at = datetime.now()
                session.commit()
                return {
                    "status": "unbillable",
                    "reason": "no_rate",
                    "destination": destination,
                }

            # Calculate sell cost
            sell = self.calculate_cost(cdr.billsec, rates.sell)

            # Calculate reseller cost (what reseller pays platform)
            cost = (
                self.calculate_cost(cdr.billsec, rates.cost)
                if rates.cost
                else CostResult(
                    billable_duration=sell.billable_duration,
                    total_cost=Decimal("0.0000"),
                )
            )

            # Calculate trunk cost (what platform pays trunk provider)
            trunk_cost = (
                self.calculate_cost(cdr.billsec, rates.trunk)
                if rates.trunk
                else CostResult(
                    billable_duration=sell.billable_duration,
                    total_cost=Decimal("0.0000"),
                )
            )

            # Update CDR
            cdr.matched_prefix = rates.sell.prefix
            cdr.rate_per_minute = rates.sell.rate_per_minute
            cdr.connection_fee = rates.sell.connection_fee
            cdr.rate_group_id = user.rate_group_id
            cdr.billable_duration = sell.billable_duration
            cdr.total_cost = sell.total_cost
            cdr.reseller_cost = cost.total_cost
            cdr.trunk_cost = trunk_cost.total_cost
            cdr.status = "rated"
            cdr.rated_at = datetime.now()
            session.commit()

            logger.info(
                f"rate_call: rated [cdr={call_record_id}, "
                f"prefix={rates.sell.prefix}, sell={sell.total_cost}, "
                f"reseller={cost.total_cost}, trunk={trunk_cost.total_cost}]"
            )

            return {
                "status": "rated",
                "call_record_id": call_record_id,
                "matched_prefix": rates.sell.prefix,
                "total_cost": str(sell.total_cost),
                "reseller_cost": str(cost.total_cost),
                "trunk_cost": str(trunk_cost.total_cost),
                "billable_duration": sell.billable_duration,
            }

    # ─────────────────────────────────────────────────────
    # Cache management
    # ─────────────────────────────────────────────────────

    def clear_trie_cache(self, rate_group_id: Optional[int] = None) -> None:
        """
        Clear trie cache. Call after rate imports/updates.
        PHP equivalent: RatingService::clearCache()
        """
        if rate_group_id:
            keys_to_remove = [
                k for k in self._trie_cache
                if k.startswith(f"{rate_group_id}:")
            ]
            for key in keys_to_remove:
                del self._trie_cache[key]
            logger.info(f"Cleared trie cache for rate_group {rate_group_id}")
        else:
            self._trie_cache.clear()
            logger.info("Cleared all trie caches")

    @staticmethod
    def _dict_to_rate(data: dict) -> Rate:
        """Reconstruct a Rate object from cached dict."""
        rate = Rate()
        rate.id = data["id"]
        rate.rate_group_id = data["rate_group_id"]
        rate.prefix = data["prefix"]
        rate.destination = data.get("destination")
        rate.rate_per_minute = Decimal(data["rate_per_minute"])
        rate.connection_fee = Decimal(data["connection_fee"])
        rate.min_duration = data.get("min_duration", 0)
        rate.billing_increment = data.get("billing_increment", 6)
        rate.effective_date = data.get("effective_date")
        rate.end_date = data.get("end_date")
        return rate
