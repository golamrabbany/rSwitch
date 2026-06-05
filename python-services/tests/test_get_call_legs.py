"""Tests for AMIListener.get_call_legs().

panoramisk and pydantic_settings are not installed in the CI/local test
environment. Stub them before importing ami_listener so the module-level
import succeeds without a real AMI connection.
"""
import sys
import types

# ── minimal panoramisk stub ──────────────────────────────────────────────────
_panoramisk = types.ModuleType("panoramisk")
_panoramisk.Manager = object  # AMIListener only references Manager as a type hint
sys.modules.setdefault("panoramisk", _panoramisk)

# ── minimal pydantic_settings stub ──────────────────────────────────────────
_ps = types.ModuleType("pydantic_settings")

class _BaseSettings:
    """Minimal stand-in: read class-level defaults only, ignore env/dotenv."""
    def __init__(self, **kwargs):
        for name, default in type(self).__dict__.items():
            if not name.startswith("_") and not callable(default) and not isinstance(default, (classmethod, staticmethod, property)):
                setattr(self, name, default)

_ps.BaseSettings = _BaseSettings
sys.modules.setdefault("pydantic_settings", _ps)

# ── now it's safe to import ──────────────────────────────────────────────────
from monitoring.ami_listener import AMIListener, ActiveCall  # noqa: E402


# ── helpers ──────────────────────────────────────────────────────────────────

def _call(unique_id, linked_id, channel):
    """Build an ActiveCall with (unique_id, channel, linked_id) — matching the
    real constructor signature: ActiveCall(unique_id, channel, linked_id="")."""
    return ActiveCall(unique_id, channel, linked_id)


# ── tests ────────────────────────────────────────────────────────────────────

def test_caller_leg_is_left_callee_is_right():
    ami = AMIListener()
    a = _call("100.1", "100.1", "PJSIP/alice-0001")   # originator -> left
    b = _call("100.2", "100.1", "PJSIP/trunk1-0002")  # other leg -> right
    ami._active_calls = {"100.1": a, "100.2": b}

    caller_leg, callee_leg = ami.get_call_legs("100.1")
    assert caller_leg.channel == "PJSIP/alice-0001"
    assert callee_leg.channel == "PJSIP/trunk1-0002"


def test_single_leg_returns_no_callee():
    ami = AMIListener()
    a = _call("200.1", "200.1", "PJSIP/bob-0001")
    ami._active_calls = {"200.1": a}

    caller_leg, callee_leg = ami.get_call_legs("200.1")
    assert caller_leg.channel == "PJSIP/bob-0001"
    assert callee_leg is None


def test_unknown_linked_id_returns_none():
    ami = AMIListener()
    ami._active_calls = {}
    assert ami.get_call_legs("nope") is None
