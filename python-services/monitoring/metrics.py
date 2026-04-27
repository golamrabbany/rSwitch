"""
Prometheus metrics for rswitch-api.

Exposes:
- AGI handler latency (histogram, per-script)
- AGI request counter (per-script, per-outcome)
- Active calls gauges (deduped per linked_id, broken down by flow+state)
- Active channel gauge (raw, including both legs of each call)
- SIP registered contacts gauge
- Live-monitoring WS client gauge
- AMI connectivity gauge
- Trunk reachability gauge (per endpoint)

The /metrics endpoint in main.py calls render_metrics() which refreshes
the gauges from current AMI listener state immediately before generating
the response. Counters/histograms are updated in-place by their call sites.
"""

import logging
import time
from typing import Tuple

from prometheus_client import (
    Counter,
    Gauge,
    Histogram,
    REGISTRY,
    CONTENT_TYPE_LATEST,
    generate_latest,
)

logger = logging.getLogger(__name__)


# ─── AGI handler instrumentation ──────────────────────────────────────────────

AGI_REQUESTS_TOTAL = Counter(
    "rswitch_agi_requests_total",
    "AGI requests received from Asterisk by script and outcome",
    ["script", "outcome"],  # outcome: success | error | unknown_script
)

AGI_HANDLER_SECONDS = Histogram(
    "rswitch_agi_handler_seconds",
    "AGI handler end-to-end latency (connect → close) in seconds",
    ["script"],
    # Buckets sized for the observed range: idle ~16ms, target 50-70 cps stays
    # under 100ms p95, capacity cliff lives between 100 and 500 ms.
    buckets=(0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0),
)


# ─── AMI / call state — refreshed from AMI listener on each scrape ────────────

ACTIVE_CALLS = Gauge(
    "rswitch_active_calls",
    "Active calls (deduped by linked_id — one entry per call) by flow and state",
    ["call_flow", "state"],
)

ACTIVE_CHANNELS = Gauge(
    "rswitch_active_channels",
    "Active Asterisk channels — undeduplicated (~2 per call: caller leg + dialed leg)",
)

REGISTERED_CONTACTS = Gauge(
    "rswitch_sip_registered_contacts",
    "Number of SIP endpoints currently registered (ps_contacts)",
)

WS_CLIENTS = Gauge(
    "rswitch_ws_clients",
    "Connected WebSocket clients receiving live-call updates",
)

AMI_CONNECTED = Gauge(
    "rswitch_ami_connected",
    "1 if rswitch-api is connected to the Asterisk AMI socket, 0 otherwise",
)

TRUNK_AVAILABLE = Gauge(
    "rswitch_trunk_available",
    "Trunk endpoint reachability — 1 if the qualify status is Avail, 0 otherwise",
    ["endpoint"],
)

# Always-known label combinations, reset to 0 each scrape so a state vanishing
# from the listener is reflected (rather than a stale gauge value).
_KNOWN_FLOWS = ("inbound", "outbound", "unknown")
_KNOWN_STATES = ("ringing", "answered")


def refresh_from_ami_listener() -> None:
    """Sample current AMI listener state into the gauge series.

    Called by the /metrics handler immediately before generating output, so
    the gauges always reflect the moment of the scrape. Handles the listener
    being unavailable (e.g. during early startup) without raising.
    """
    try:
        from monitoring.ami_listener import get_ami_listener
        ami = get_ami_listener()
    except Exception as e:
        logger.debug(f"metrics: AMI listener not available: {e}")
        return

    # Active calls — deduped by linked_id (matches the stats in get_stats)
    counts = {(f, s): 0 for f in _KNOWN_FLOWS for s in _KNOWN_STATES}
    try:
        for call in ami._dedupe_legs():
            flow = call.call_flow if call.call_flow in _KNOWN_FLOWS else "unknown"
            state = call.state if call.state in _KNOWN_STATES else "ringing"
            counts[(flow, state)] = counts.get((flow, state), 0) + 1
    except Exception as e:
        logger.debug(f"metrics: dedupe_legs failed: {e}")
    for (flow, state), n in counts.items():
        ACTIVE_CALLS.labels(call_flow=flow, state=state).set(n)

    # Channel gauge: raw (every leg counted separately)
    try:
        ACTIVE_CHANNELS.set(len(ami._active_calls))
    except Exception:
        pass

    try:
        REGISTERED_CONTACTS.set(len(ami._registered_contacts))
    except Exception:
        pass

    try:
        WS_CLIENTS.set(len(ami._ws_clients))
    except Exception:
        pass

    try:
        AMI_CONNECTED.set(1 if ami.is_connected else 0)
    except Exception:
        AMI_CONNECTED.set(0)

    # Trunk status — set 1 for known-available, 0 for everything else
    try:
        for endpoint, status in ami._trunk_status.items():
            TRUNK_AVAILABLE.labels(endpoint=endpoint).set(1 if status == "Avail" else 0)
    except Exception:
        pass


def render_metrics() -> Tuple[bytes, str]:
    """Refresh listener-driven gauges and return (body, content_type) for
    the /metrics HTTP response."""
    refresh_from_ami_listener()
    return generate_latest(REGISTRY), CONTENT_TYPE_LATEST


# ─── Helper for instrumenting AGI handler dispatch ────────────────────────────

class agi_request_timer:
    """Context manager that records the elapsed time and outcome of an
    AGI request to the histogram and counter above.

    Usage:
        async with agi_request_timer(script) as t:
            ... handle the request ...
            # on exception, t.outcome = "error" is set automatically
            # on unknown script, set t.outcome = "unknown_script" before exit
    """

    __slots__ = ("script", "_start", "outcome")

    def __init__(self, script: str):
        self.script = script or "unknown"
        self.outcome = "success"
        self._start = 0.0

    def __enter__(self):
        self._start = time.perf_counter()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        elapsed = time.perf_counter() - self._start
        AGI_HANDLER_SECONDS.labels(script=self.script).observe(elapsed)
        outcome = "error" if exc_type is not None else self.outcome
        AGI_REQUESTS_TOTAL.labels(script=self.script, outcome=outcome).inc()
        return False  # do not suppress exceptions
