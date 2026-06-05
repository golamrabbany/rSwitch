"""Verification for the live-listen HMAC token minted by Laravel."""
import base64
import hashlib
import hmac
import json
from typing import Optional


def _b64url_decode(s: str) -> bytes:
    pad = "=" * (-len(s) % 4)
    return base64.urlsafe_b64decode(s + pad)


def verify_listen_token(token: str, secret: str, now: int) -> Optional[dict]:
    """Return the token claims if valid, else None.

    Valid means: well-formed, HMAC-SHA256 signature matches `secret`,
    not expired (claims['exp'] >= now), and role == 'super_admin'.
    """
    if not token or not secret:
        return None
    try:
        msg, sig = token.split(".", 1)
    except ValueError:
        return None
    expected = base64.urlsafe_b64encode(
        hmac.new(secret.encode(), msg.encode(), hashlib.sha256).digest()
    ).rstrip(b"=").decode()
    if not hmac.compare_digest(sig, expected):
        return None
    try:
        claims = json.loads(_b64url_decode(msg))
    except (ValueError, json.JSONDecodeError):
        return None
    if int(claims.get("exp", 0)) < now:
        return None
    if claims.get("role") != "super_admin":
        return None
    return claims
