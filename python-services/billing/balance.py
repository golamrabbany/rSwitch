"""
BalanceService — Python port of app/Services/BalanceService.php

Atomic credit/debit operations with row-level locking.
Uses Decimal for precision (matches PHP bcmath).
"""

import logging
from datetime import datetime
from decimal import Decimal
from typing import Optional

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
    # charge_call — Charge user for a rated CDR
    # PHP equivalent: BalanceService::chargeCall()
    # ─────────────────────────────────────────────────────

    def charge_call(self, call_record_id: int) -> Optional[Transaction]:
        """
        Charge a user for a completed, rated call.
        Opens its own session with transaction.
        """
        from shared.database import get_session

        with get_session() as session:
            cdr = session.query(CallRecord).get(call_record_id)
            if not cdr:
                raise ValueError(f"CallRecord {call_record_id} not found")

            amount = Decimal(str(cdr.total_cost or 0))

            # Zero-cost call — create record but don't debit
            if amount <= Decimal("0"):
                locked_user = (
                    session.query(User)
                    .filter(User.id == cdr.user_id)
                    .with_for_update()
                    .first()
                )
                if not locked_user:
                    return None

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
                session.commit()
                return transaction

            # Debit the user's balance
            return self.debit(
                session=session,
                user_id=cdr.user_id,
                amount=amount,
                type="call_charge",
                reference_type="call_record",
                reference_id=cdr.id,
                description=(
                    f"Call to {cdr.callee} "
                    f"({cdr.billable_duration}s @ {cdr.rate_per_minute}/min)"
                ),
            )

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
