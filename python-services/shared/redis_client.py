"""Redis client singleton and pub/sub helpers."""

import redis as redis_lib
from shared.config import get_settings

_redis_client = None


def get_redis() -> redis_lib.Redis:
    """Get singleton Redis client."""
    global _redis_client
    if _redis_client is None:
        settings = get_settings()
        _redis_client = redis_lib.from_url(
            settings.redis_url,
            decode_responses=True,
        )
    return _redis_client
