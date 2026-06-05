import base64
import hashlib
import hmac
import json

from shared.listen_auth import verify_listen_token

SECRET = "test-secret-123"


def _b64url(raw: bytes) -> str:
    return base64.urlsafe_b64encode(raw).rstrip(b"=").decode()


def _make_token(payload: dict, secret: str = SECRET) -> str:
    msg = _b64url(json.dumps(payload).encode())
    sig = _b64url(hmac.new(secret.encode(), msg.encode(), hashlib.sha256).digest())
    return f"{msg}.{sig}"


def test_valid_token_returns_claims():
    payload = {"lid": "1700000000.5", "uid": 15192, "role": "super_admin", "exp": 9999999999}
    claims = verify_listen_token(_make_token(payload), SECRET, now=1000)
    assert claims["lid"] == "1700000000.5"
    assert claims["uid"] == 15192


def test_expired_token_rejected():
    payload = {"lid": "x", "uid": 1, "role": "super_admin", "exp": 500}
    assert verify_listen_token(_make_token(payload), SECRET, now=1000) is None


def test_wrong_role_rejected():
    payload = {"lid": "x", "uid": 1, "role": "admin", "exp": 9999999999}
    assert verify_listen_token(_make_token(payload), SECRET, now=1000) is None


def test_bad_signature_rejected():
    payload = {"lid": "x", "uid": 1, "role": "super_admin", "exp": 9999999999}
    token = _make_token(payload, secret="WRONG")
    assert verify_listen_token(token, SECRET, now=1000) is None


def test_malformed_token_rejected():
    assert verify_listen_token("not-a-token", SECRET, now=1000) is None
    assert verify_listen_token("", SECRET, now=1000) is None
