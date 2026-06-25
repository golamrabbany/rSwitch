"""
Pure formatting helpers for voice broadcast — kept dependency-free (stdlib
only) so they unit-test without celery/sqlalchemy/redis, the same way
billing.number_format is.

Two contracts live here:

* build_broadcast_cdr_params — the row a broadcast call inserts into
  call_records. It is a *billable, in_progress* sip_to_trunk CDR carrying
  reseller_id + outgoing_trunk_id and NO inline cost, so the shared
  rating/charging pipeline (billing.tasks.rate_and_charge) rates it exactly
  like a regular outbound call: BD-MSISDN normalize, increment policy, dual
  client+reseller billing, trunk cost. Timestamps are supplied by the caller
  (engine-local datetime.now()), never UTC.

* build_call_file_content — the Asterisk .call file. It now also passes the
  outgoing trunk id and the client's reseller id so the AGI handler can
  populate those CDR columns.
"""
import json


def build_broadcast_cdr_params(
    *,
    uuid,
    user_id,
    sip_account_id,
    reseller_id,
    outgoing_trunk_id,
    broadcast_id,
    caller,
    callee,
    caller_id,
    call_start,
    call_end,
    duration,
):
    """Build the named-bind params for the broadcast CDR INSERT.

    The CDR is intentionally created *unrated* (status='in_progress', no cost
    columns): the shared rater fills total_cost / reseller_cost / trunk_cost /
    matched_prefix / billable_duration. billsec drives the rating and, for a
    post-answer broadcast leg, equals the answered playback duration.
    """
    return {
        "uuid": uuid,
        "user_id": user_id,
        "sip_account_id": sip_account_id,
        "reseller_id": reseller_id,
        "outgoing_trunk_id": outgoing_trunk_id,
        "broadcast_id": broadcast_id,
        "caller": caller,
        "callee": callee,
        "caller_id": caller_id,
        "call_start": call_start,
        "call_end": call_end,
        "duration": duration,
        "billsec": duration,
        "call_flow": "sip_to_trunk",
        "call_type": "broadcast",
        "disposition": "ANSWERED",
        "status": "in_progress",
    }


def build_call_file_content(
    number,
    broadcast,
    dial_string,
    *,
    outgoing_trunk_id,
    reseller_id,
):
    """Render the Asterisk .call file body for one broadcast number.

    reseller_id is rendered as an empty value when the client has no reseller
    (direct client under super_admin); the handler reads that back as NULL.
    """
    survey_config = (
        json.dumps(broadcast.survey_config) if broadcast.survey_config else "{}"
    )
    reseller_val = "" if reseller_id is None else reseller_id

    return f"""Channel: {dial_string}
CallerID: "{broadcast.caller_id_name or 'Broadcast'}" <{broadcast.caller_id_number or '0000'}>
MaxRetries: {broadcast.retry_attempts or 0}
RetryTime: {broadcast.retry_delay or 300}
WaitTime: {broadcast.ring_timeout or 30}
Context: from-broadcast
Extension: s
Priority: 1
Set: BROADCAST_ID={broadcast.id}
Set: BROADCAST_NUMBER_ID={number.id}
Set: VOICE_FILE={broadcast.voice_file_path}
Set: BROADCAST_TYPE={broadcast.type}
Set: SURVEY_CONFIG={survey_config}
Set: RSWITCH_USER_ID={broadcast.user_id}
Set: RSWITCH_SIP_ACCOUNT_ID={broadcast.sip_account_id}
Set: RSWITCH_OUTGOING_TRUNK_ID={outgoing_trunk_id}
Set: RSWITCH_RESELLER_ID={reseller_val}
Set: BROADCAST_CALLEE={number.phone_number}
"""
