"""
BalanceService — Python port of app/Services/BalanceService.php

Atomic credit/debit operations with row-level locking.
Uses Decimal for precision (matches PHP bcmath).
"""

import logging
import os
from datetime import datetime
from decimal import Decimal
from typing import Optional

import redis as redis_lib
from sqlalchemy import text
from sqlalchemy.orm import Session

from billing.exceptions import InsufficientBalanceException
from shared.models.user import User
from shared.models.call_record import CallRecord
from shared.models.transaction import Transaction

logger = logging.getLogger(__name__)


class BalanceService:
    """
    Atomic balance operations with row-level locking.

    Equivalent to PHP BalanceService with these methods:
    - debit()             → debit()
    - credit()            → credit()
    - canAffordCall()     → can_afford_call()
    - chargeCall()        → charge_call()
    - getAvailableBalance → get_available_balance()
    """

    # ─────────────────────────────────────────────────────
    # debit — Atomic balance deduction with row lock
    # PHP equivalent: BalanceService::debit()
    # ─────────────────────────────────────────────────────

    def debit(
        self,
        session: Session,
        user_id: int,
        amount: Decimal,
        type: str,
        reference_type: Optional[str] = None,
        reference_id: Optional[int] = None,
        description: str = "",
        created_by: Optional[int] = None,
        source: Optional[str] = None,
        remarks: Optional[str] = None,
        create_transaction: bool = True,
    ) -> Optional[Transaction]:
        """
        Debit a user's balance atomically with row-level locking.
        Raises InsufficientBalanceException for prepaid users with low balance.

        Set create_transaction=False for call charges (aggregated daily).
        """
        if amount <= Decimal("0"):
            raise ValueError("Debit amount must be positive")

        # Lock the user row (SELECT ... FOR UPDATE)
        locked_user = (
            session.query(User)
            .filter(User.id == user_id)
            .with_for_update()
            .first()
        )

        if not locked_user:
            raise RuntimeError(f"User {user_id} not found during balance debit")

        # Prepaid balance check (same as PHP)
        if locked_user.is_prepaid():
            available = Decimal(str(locked_user.balance)) + Decimal(
                str(locked_user.credit_limit)
            )
            if available < amount:
                raise InsufficientBalanceException(user_id, amount, available)

        # Calculate new balance
        new_balance = (
            Decimal(str(locked_user.balance)) - amount
        ).quantize(Decimal("0.0001"))

        # Update user balance
        locked_user.balance = new_balance

        # Skip transaction record for call charges (aggregated daily at midnight)
        if not create_transaction:
            session.flush()
            logger.info(
                f"debit (no txn): user={user_id}, amount={amount}, "
                f"new_balance={new_balance}, type={type}"
            )
            return None

        # Create transaction record
        transaction = Transaction()
        transaction.user_id = user_id
        transaction.type = type
        transaction.amount = -amount  # Negative for debit
        transaction.balance_after = new_balance
        transaction.reference_type = reference_type
        transaction.reference_id = reference_id
        transaction.description = description
        transaction.source = source
        transaction.remarks = remarks
        transaction.created_by = created_by
        transaction.created_at = datetime.now()

        session.add(transaction)
        session.flush()

        logger.info(
            f"debit: user={user_id}, amount={amount}, "
            f"new_balance={new_balance}, type={type}"
        )

        return transaction

    # ─────────────────────────────────────────────────────
    # credit — Atomic balance addition with row lock
    # PHP equivalent: BalanceService::credit()
    # ─────────────────────────────────────────────────────

    def credit(
        self,
        session: Session,
        user_id: int,
        amount: Decimal,
        type: str,
        reference_type: Optional[str] = None,
        reference_id: Optional[int] = None,
        description: str = "",
        created_by: Optional[int] = None,
        source: Optional[str] = None,
        remarks: Optional[str] = None,
    ) -> Transaction:
        """Credit a user's balance atomically with row lock."""
        if amount <= Decimal("0"):
            raise ValueError("Credit amount must be positive")

        locked_user = (
            session.query(User)
            .filter(User.id == user_id)
            .with_for_update()
            .first()
        )

        if not locked_user:
            raise RuntimeError(f"User {user_id} not found during balance credit")

        new_balance = (
            Decimal(str(locked_user.balance)) + amount
        ).quantize(Decimal("0.0001"))

        locked_user.balance = new_balance

        transaction = Transaction()
        transaction.user_id = user_id
        transaction.type = type
        transaction.amount = amount
        transaction.balance_after = new_balance
        transaction.reference_type = reference_type
        transaction.reference_id = reference_id
        transaction.description = description
        transaction.source = source
        transaction.remarks = remarks
        transaction.created_by = created_by
        transaction.created_at = datetime.now()

        session.add(transaction)
        session.flush()

        logger.info(
            f"credit: user={user_id}, amount={amount}, "
            f"new_balance={new_balance}, type={type}"
        )

        # Auto-unblock reseller if balance restored above zero
        if new_balance > Decimal("0"):
            self._unblock_reseller(user_id)

        return transaction

    # ─────────────────────────────────────────────────────
    # can_afford_call — Balance check for call authorization
    # PHP equivalent: BalanceService::canAffordCall()
    # ─────────────────────────────────────────────────────

    def can_afford_call(
        self,
        session: Session,
        user_id: int,
        estimated_cost: Decimal = Decimal("0"),
    ) -> bool:
        """Check if a user can afford a call."""
        locked_user = (
            session.query(User)
            .filter(User.id == user_id)
            .with_for_update()
            .first()
        )

        if not locked_user:
            return False

        balance = Decimal(str(locked_user.balance))
        credit_limit = Decimal(str(locked_user.credit_limit))
        available = balance + credit_limit

        if locked_user.is_prepaid():
            min_balance = Decimal(str(locked_user.min_balance_for_calls or 0))
            required = min_balance + estimated_cost
            return available >= required

        # Postpaid: balance can go negative but not below -credit_limit
        floor = -credit_limit
        after_charge = balance - estimated_cost
        return after_charge >= floor

    # ─────────────────────────────────────────────────────
    # charge_call — Charge client + reseller in ONE transaction
    # PHP equivalent: BalanceService::chargeCall()
    # ─────────────────────────────────────────────────────

    def charge_call(self, call_record_id: int) -> dict:
        """
        Charge a client (and optionally their reseller) for a rated call.

        Balance is deducted in real-time but NO Transaction records are created.
        Transactions are aggregated daily at midnight by the daily_call_summary task.
        CDR status is set to 'charged' for idempotency (prevents double charge on retry).

        Both deductions happen in a SINGLE database transaction.
        If reseller debit fails, client charge still commits.
        """
        from shared.database import get_session

        with get_session() as session:
            cdr = session.query(CallRecord).get(call_record_id)
            if not cdr:
                raise ValueError(f"CallRecord {call_record_id} not found")

            # ── Non-billable call flows: no balance deduction ──
            # Transit (trunk_to_trunk): invoice-based, no user balance
            # Inbound (trunk_to_sip): free for receiver
            # P2P (sip_to_sip): internal calls, no charge
            if cdr.call_flow in ("trunk_to_trunk", "trunk_to_sip", "sip_to_sip"):
                cdr.status = "charged"
                session.commit()
                logger.info(f"charge_call: {cdr.call_flow} — no balance deduction [cdr={call_record_id}]")
                return {"client_charged": False, "reseller_charged": False}

            # ── Idempotency: skip if already charged ──
            if cdr.status == "charged":
                logger.info(
                    f"charge_call: SKIPPED — already charged [cdr={call_record_id}]"
                )
                return {
                    "client_charged": True,
                    "reseller_charged": True,
                }

            result = {
                "client_charged": False,
                "reseller_charged": False,
            }

            client_amount = Decimal(str(cdr.total_cost or 0))
            reseller_amount = Decimal(str(cdr.reseller_cost or 0))

            # ── Step 1: Debit client balance (no Transaction record) ──
            # Call already happened — mark CDR as 'charged' regardless of balance.
            # If client can't pay, log it but don't leave CDR stuck at 'rated'.
            if client_amount > Decimal("0"):
                try:
                    self.debit(
                        session=session,
                        user_id=cdr.user_id,
                        amount=client_amount,
                        type="call_charge",
                        create_transaction=False,
                    )
                    result["client_charged"] = True
                except InsufficientBalanceException as e:
                    logger.warning(
                        f"charge_call: client insufficient balance "
                        f"[cdr={call_record_id}, user={e.user_id}, "
                        f"amount={e.amount}, available={e.available}] "
                        f"— CDR still marked charged (call already happened)"
                    )
                    result["client_charged"] = False

            # ── Step 2: Debit reseller balance (no Transaction record) ──
            # Uses cdr.reseller_id (captured at call time)
            if reseller_amount > Decimal("0") and cdr.reseller_id:
                parent = session.query(User).get(cdr.reseller_id)
                if parent and parent.role != 'super_admin':
                    try:
                        self.debit(
                            session=session,
                            user_id=parent.id,
                            amount=reseller_amount,
                            type="reseller_call_charge",
                            create_transaction=False,
                        )
                        result["reseller_charged"] = True
                    except InsufficientBalanceException as e:
                        logger.warning(
                            f"charge_call: reseller insufficient balance "
                            f"[cdr={call_record_id}, reseller={e.user_id}, "
                            f"amount={e.amount}, available={e.available}] "
                            f"— client still charged, blocking reseller"
                        )
                        result["reseller_charged"] = False
                        self._block_reseller(parent.id)

            # ── Mark CDR as charged (idempotency marker) ──
            cdr.status = "charged"

            # ── Single commit: balance deductions + CDR status ──
            session.commit()

            logger.info(
                f"charge_call: cdr={call_record_id}, "
                f"client={client_amount}, reseller={reseller_amount}, "
                f"client_charged={result['client_charged']}, "
                f"reseller_charged={result['reseller_charged']}"
            )

            return result

    # ─────────────────────────────────────────────────────
    # get_available_balance
    # PHP equivalent: BalanceService::getAvailableBalance()
    # ─────────────────────────────────────────────────────

    @staticmethod
    def get_available_balance(user: User) -> Decimal:
        """Get a user's effective available balance for calls."""
        return (
            Decimal(str(user.balance)) + Decimal(str(user.credit_limit))
        ).quantize(Decimal("0.0001"))

    # ─────────────────────────────────────────────────────
    # Reseller auto-cutoff helpers
    # ─────────────────────────────────────────────────────

    _redis_pool = None

    def _get_redis(self) -> redis_lib.Redis:
        """Get Redis client from shared connection pool (created once, reused)."""
        if BalanceService._redis_pool is None:
            redis_url = os.environ.get("REDIS_URL", "redis://127.0.0.1:6379/0")
            BalanceService._redis_pool = redis_lib.ConnectionPool.from_url(
                redis_url, max_connections=10
            )
        return redis_lib.Redis(connection_pool=BalanceService._redis_pool)

    def _block_reseller(self, reseller_id: int) -> None:
        """
        Block a reseller (insufficient balance) and hangup their active calls.
        1. Set Redis flag → new calls rejected instantly by AGI
        2. Trigger async Celery task → hangup all active calls
        """
        try:
            r = self._get_redis()
            key = f"rswitch:reseller_blocked:{reseller_id}"
            r.setex(key, 86400, "insufficient_balance")  # 24h TTL safety
            logger.info(f"Reseller {reseller_id} BLOCKED — Redis flag set")
        except Exception as e:
            logger.error(f"Failed to set reseller block flag: {e}")

        # Trigger async hangup of all active calls for this reseller
        try:
            from billing.tasks import hangup_reseller_calls
            hangup_reseller_calls.delay(reseller_id)
            logger.info(f"Triggered hangup task for reseller {reseller_id}")
        except Exception as e:
            logger.error(f"Failed to trigger hangup task: {e}")

    def _unblock_reseller(self, user_id: int) -> None:
        """
        Clear the reseller blocked flag if balance is now positive.
        Called automatically after any credit operation.
        """
        try:
            r = self._get_redis()
            key = f"rswitch:reseller_blocked:{user_id}"
            if r.exists(key):
                r.delete(key)
                logger.info(f"Reseller {user_id} UNBLOCKED — balance restored")
        except Exception as e:
            logger.error(f"Failed to clear reseller block flag: {e}")
