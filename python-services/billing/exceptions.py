from decimal import Decimal


class RateNotFoundException(Exception):
    """No matching rate found for the given destination and rate group."""

    def __init__(self, destination: str, rate_group_id: int):
        self.destination = destination
        self.rate_group_id = rate_group_id
        super().__init__(
            f"No rate found for destination '{destination}' "
            f"in rate group {rate_group_id}"
        )


class InsufficientBalanceException(Exception):
    """User does not have enough balance for the operation."""

    def __init__(self, user_id: int, amount: Decimal, available: Decimal):
        self.user_id = user_id
        self.amount = amount
        self.available = available
        super().__init__(
            f"Insufficient balance for user {user_id}: "
            f"needs {amount}, available {available}"
        )
