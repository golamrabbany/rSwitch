"""
Voice-broadcast billing must reuse the shared rating/charging pipeline
(billing.tasks.rate_and_charge → rate_call + charge_call) instead of
hand-rolling cost. Hand-rolled billing skipped the BD-MSISDN normalize fix,
the increment policy, dual reseller billing, trunk cost, and stored UTC
timestamps — so broadcast calls mis-billed (often cost=0).

These pure-logic tests lock the two contracts that make the reuse work:
  1. the broadcast CDR is created as a *billable, in_progress* trunk call,
     carrying reseller_id + outgoing_trunk_id (so dual + trunk billing fire),
     with a caller-supplied LOCAL call_start (not datetime.utcnow), and no
     inline cost (rating fills it); and
  2. the .call file carries the trunk + reseller ids the handler needs.
"""
from datetime import datetime
from types import SimpleNamespace

from broadcast.formatting import build_broadcast_cdr_params, build_call_file_content


def test_broadcast_cdr_is_billable_in_progress():
    start = datetime(2026, 6, 25, 10, 0, 0)
    end = datetime(2026, 6, 25, 10, 0, 18)
    params = build_broadcast_cdr_params(
        uuid="u1", user_id=42, sip_account_id=7, reseller_id=5,
        outgoing_trunk_id=4, broadcast_id=9, caller="09603128999",
        callee="01714101351", caller_id="09603128999",
        call_start=start, call_end=end, duration=18,
    )
    # sip_to_trunk so charge_call actually deducts (not transit/inbound/p2p)
    assert params["call_flow"] == "sip_to_trunk"
    # in_progress so rate_and_charge / rate_batch pick it up and rate it
    assert params["status"] == "in_progress"
    assert params["call_type"] == "broadcast"
    assert params["disposition"] == "ANSWERED"
    # billsec drives rating; for a post-answer broadcast it equals duration
    assert params["billsec"] == 18
    assert params["duration"] == 18
    # NO inline cost — the shared rater fills these
    assert "total_cost" not in params
    assert "rate_per_minute" not in params
    assert "connection_fee" not in params


def test_broadcast_cdr_carries_reseller_and_trunk():
    params = build_broadcast_cdr_params(
        uuid="u1", user_id=42, sip_account_id=7, reseller_id=5,
        outgoing_trunk_id=4, broadcast_id=9, caller="x",
        callee="01714101351", caller_id="x",
        call_start=datetime(2026, 6, 25, 10, 0, 0),
        call_end=datetime(2026, 6, 25, 10, 0, 1), duration=1,
    )
    # dual-billing (reseller) + trunk-cost need these captured at CDR creation
    assert params["reseller_id"] == 5
    assert params["outgoing_trunk_id"] == 4
    assert params["broadcast_id"] == 9


def test_broadcast_cdr_uses_supplied_local_time_not_utcnow():
    start = datetime(2026, 6, 25, 10, 0, 0)
    params = build_broadcast_cdr_params(
        uuid="u1", user_id=42, sip_account_id=7, reseller_id=None,
        outgoing_trunk_id=4, broadcast_id=9, caller="x",
        callee="01714101351", caller_id="x",
        call_start=start, call_end=start, duration=1,
    )
    # the caller injects the engine's LOCAL datetime.now(); the builder must
    # pass it through untouched (never substitute datetime.utcnow()).
    assert params["call_start"] is start
    assert params["call_end"] is start
    # reseller_id may be absent (direct client under super_admin) — still valid
    assert params["reseller_id"] is None


def test_call_file_includes_trunk_and_reseller_vars():
    number = SimpleNamespace(id=3, phone_number="01714101351")
    broadcast = SimpleNamespace(
        id=9, type="simple", survey_config=None,
        caller_id_name="RG", caller_id_number="0960",
        retry_attempts=0, retry_delay=300, ring_timeout=30,
        voice_file_path="/var/spool/asterisk/voicebroadcast/1.wav",
        user_id=42, sip_account_id=7,
    )
    content = build_call_file_content(
        number, broadcast, "PJSIP/8801714101351@trunk-both-4",
        outgoing_trunk_id=4, reseller_id=5,
    )
    # new vars the handler needs to build a dual/trunk-billable CDR
    assert "Set: RSWITCH_OUTGOING_TRUNK_ID=4" in content
    assert "Set: RSWITCH_RESELLER_ID=5" in content
    # existing essentials still present
    assert "Set: BROADCAST_ID=9" in content
    assert "Set: BROADCAST_NUMBER_ID=3" in content
    assert "Set: BROADCAST_CALLEE=01714101351" in content
    assert "Context: from-broadcast" in content


def test_call_file_reseller_var_blank_when_no_reseller():
    number = SimpleNamespace(id=3, phone_number="01714101351")
    broadcast = SimpleNamespace(
        id=9, type="simple", survey_config=None,
        caller_id_name=None, caller_id_number=None,
        retry_attempts=0, retry_delay=300, ring_timeout=30,
        voice_file_path="/x.wav", user_id=42, sip_account_id=7,
    )
    # direct client (no reseller) → reseller_id None renders as empty value,
    # which the handler reads back as "no reseller" (NULL reseller_id).
    content = build_call_file_content(
        number, broadcast, "PJSIP/8801714101351@trunk-both-4",
        outgoing_trunk_id=4, reseller_id=None,
    )
    assert "Set: RSWITCH_RESELLER_ID=" in content
