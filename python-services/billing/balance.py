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
    ) -> Transaction:
        """
        Debit a user's balance atomically with row-level locking.
        Raises InsufficientBalanceException for prepaid users with low balance.
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

        Both deductions happen in a SINGLE database transaction when possible.
        If reseller debit fails (insufficient balance), the client charge
        still commits — because the call already happened, we must not
        lose revenue from the client.

        The InsufficientBalanceException in debit() is raised BEFORE any
        DB writes (it's a Python-level check), so the session stays clean
        and the client's pending changes can still commit safely.

        Returns dict with charge results.
        """
        from shared.database import get_session

        with get_session() as session:
            cdr = session.query(CallRecord).get(call_record_id)
            if not cdr:
                raise ValueError(f"CallRecord {call_record_id} not found")

            # ── Idempotency check: prevent double charge on Celery retry ──
            existing_txn = (
                session.query(Transaction)
                .filter(
                    Transaction.reference_type == "call_record",
                    Transaction.reference_id == cdr.id,
                    Transaction.type == "call_charge",
                )
                .first()
            )
            if existing_txn:
                logger.info(
                    f"charge_call: SKIPPED — already charged "
                    f"[cdr={call_record_id}, txn={existing_txn.id}]"
                )
                return {
                    "client_transaction": existing_txn,
                    "reseller_transaction": None,
                    "client_charged": True,
                    "reseller_charged": True,  # Assume reseller was handled
                }

            result = {
                "client_transaction": None,
                "reseller_transaction": None,
                "client_charged": False,
                "reseller_charged": False,
            }

            client_amount = Decimal(str(cdr.total_cost or 0))
            reseller_amount = Decimal(str(cdr.reseller_cost or 0))

            # ── Step 1: Charge the client ──
            if client_amount > Decimal("0"):
                client_txn = self.debit(
                    session=session,
                    user_id=cdr.user_id,
                    amount=client_amount,
                    type="call_charge",
                    reference_type="call_record",
                    reference_id=cdr.id,
                    description=(
                        f"Call to {cdr.callee} "
                        f"({cdr.billable_duration}s @ {cdr.rate_per_minute}/min)"
                    ),
                )
                result["client_transaction"] = client_txn
                result["client_charged"] = True
            else:
                # Zero-cost call — log a zero transaction
                locked_user = (
                    session.query(User)
                    .filter(User.id == cdr.user_id)
                    .with_for_update()
                    .first()
                )
                if locked_user:
                    transaction = Transaction()
                    transaction.user_id = locked_user.id
                    transaction.type = "call_charge"
                    transaction.amount = Decimal("0.0000")
                    transaction.balance_after = Decimal(str(locked_user.balance))
                    transaction.reference_type = "call_record"
                    transaction.reference_id = cdr.id
                    transaction.description = (
                        f"Call to {cdr.callee} ({cdr.billable_duration}s) - zero cost"
                    )
                    transaction.created_at = datetime.now()
                    session.add(transaction)
                    session.flush()
                    result["client_transaction"] = transaction
                    result["client_charged"] = True

            # ── Step 2: Charge the reseller (same transaction) ──
            # Uses cdr.reseller_id (captured at call time) — NOT user.parent_id
            # (which could change if client moved between resellers after call).
            # If reseller debit fails, client charge still commits.
            if reseller_amount > Decimal("0") and cdr.reseller_id:
                parent = session.query(User).get(cdr.reseller_id)
                if parent and parent.role != 'super_admin':
                    try:
                        reseller_txn = self.debit(
                            session=session,
                            user_id=parent.id,
                            amount=reseller_amount,
                            type="reseller_call_charge",
                            reference_type="call_record",
                            reference_id=cdr.id,
                            description=(
                                f"Reseller cost: call to {cdr.callee} "
                                f"({cdr.billable_duration}s)"
                            ),
                        )
                        result["reseller_transaction"] = reseller_txn
                        result["reseller_charged"] = True
                    except InsufficientBalanceException as e:
                        # Reseller can't pay — but call already happened.
                        # Client charge must still commit. Reseller owes a debt.
                        logger.warning(
                            f"charge_call: reseller insufficient balance "
                            f"[cdr={call_record_id}, reseller={e.user_id}, "
                            f"amount={e.amount}, available={e.available}] "
                            f"— client still charged, blocking reseller"
                        )
                        result["reseller_charged"] = False

                        # Block reseller + hangup active calls
                        self._block_reseller(parent.id)

            # ── Commit: client always charged, reseller if possible ──
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
