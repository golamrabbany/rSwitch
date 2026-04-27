"""
FastAGI load test — drives the rswitch-api AGI server directly at a target
calls-per-second rate to validate Tier D async-DB performance.

Bypasses Asterisk/SIPp by speaking the AGI protocol on TCP. Simulates either
'route_outbound' (outbound flow) or 'call_end' (hangup flow). Measures end-to-end
AGI latency from connect → final 200 → close.

Usage on the engine box:

    # Soak at 70 cps for 5 minutes, half outbound + half call-end:
    python3 load_tests/agi_load_test.py --rate 70 --duration 300 \
        --mix outbound,call_end --target 127.0.0.1:4573

    # Burst test 100 cps for 30s:
    python3 load_tests/agi_load_test.py --rate 100 --duration 30 \
        --mix outbound

Reports p50/p95/p99 latency, success rate, and a per-second throughput timeline.

Pre-conditions on the test engine:
  - rswitch-api running with the Tier D branch
  - At least one active SIP account (for outbound flow): set --sip-username
  - At least one CDR row in 'in_progress' state (for call_end flow):
    pre-create with `INSERT INTO call_records (uuid, status, call_flow, ...)`
    or omit --mix call_end and use only outbound.

Notes:
  - This does NOT exercise Asterisk's SIP/RTP path. For full-stack testing
    add SIPp on top. This isolates the Python handler hot path.
  - Outbound flow expects to fail at REJECT (no real SIP registered for the
    fake channel uniqueid) — that's fine, we measure handler latency, not
    routing success.
"""

import argparse
import asyncio
import random
import statistics
import time
import uuid
from dataclasses import dataclass


@dataclass
class Sample:
    script: str
    started_at: float
    latency_ms: float
    ok: bool
    error: str = ""


def _agi_environ(script: str, *, sip_username: str, dest: str, cdr_uuid: str) -> str:
    """Build the AGI request envelope that Asterisk would normally send.
    Each line is a key/value pair followed by a blank line that signals
    end-of-environment."""
    unique_id = f"{int(time.time())}.{random.randint(1, 9999)}"
    if script == "route_outbound":
        channel = f"PJSIP/{sip_username}-{uuid.uuid4().hex[:8]}"
        callee = dest
        agi_arg_1 = ""
    elif script == "call_end":
        channel = f"PJSIP/trunk-both-1-{uuid.uuid4().hex[:8]}"
        callee = ""
        agi_arg_1 = cdr_uuid
    else:
        raise ValueError(f"unknown script {script!r}")

    lines = [
        f"agi_network: yes",
        f"agi_network_script: {script}",
        f"agi_request: agi://{script}",
        f"agi_channel: {channel}",
        f"agi_language: en",
        f"agi_type: PJSIP",
        f"agi_uniqueid: {unique_id}",
        f"agi_version: 21.0.0",
        f"agi_callerid: 1000",
        f"agi_calleridname: Tester",
        f"agi_callingpres: 0",
        f"agi_callingani2: 0",
        f"agi_callington: 0",
        f"agi_callingtns: 0",
        f"agi_dnid: {callee}",
        f"agi_rdnis: unknown",
        f"agi_context: from-internal",
        f"agi_extension: {callee}",
        f"agi_priority: 1",
        f"agi_enhanced: 0.0",
        f"agi_accountcode: ",
        f"agi_threadid: 1",
        f"agi_arg_1: {agi_arg_1}",
    ]
    return "\n".join(lines) + "\n\n"


async def _drive_one(host: str, port: int, script: str,
                     sip_username: str, dest: str, cdr_uuid: str,
                     timeout: float = 10.0) -> Sample:
    """Open a single AGI connection, send the env, respond to GET VARIABLE
    queries with sensible defaults, and time how long until the handler
    closes the socket (or the timeout fires)."""
    start = time.perf_counter()
    try:
        reader, writer = await asyncio.wait_for(
            asyncio.open_connection(host, port), timeout=timeout
        )
    except (asyncio.TimeoutError, OSError) as e:
        return Sample(script, start, (time.perf_counter() - start) * 1000, False,
                      f"connect: {type(e).__name__}")

    try:
        writer.write(_agi_environ(
            script, sip_username=sip_username, dest=dest, cdr_uuid=cdr_uuid,
        ).encode())
        await writer.drain()

        # Respond to AGI commands until handler closes the socket.
        # The handler will issue GET VARIABLE foo / SET VARIABLE foo bar /
        # VERBOSE "..." commands; we reply with "200 result=1 (some-value)".
        deadline = start + timeout
        while True:
            remaining = deadline - time.perf_counter()
            if remaining <= 0:
                return Sample(script, start, (time.perf_counter() - start) * 1000, False, "timeout")

            try:
                line = await asyncio.wait_for(reader.readline(), timeout=remaining)
            except asyncio.TimeoutError:
                return Sample(script, start, (time.perf_counter() - start) * 1000, False, "timeout")

            if not line:
                # EOF — handler finished
                return Sample(script, start, (time.perf_counter() - start) * 1000, True)

            cmd = line.decode(errors="replace").strip()
            reply = _reply_for(cmd, script=script, dest=dest, cdr_uuid=cdr_uuid)
            writer.write((reply + "\n").encode())
            await writer.drain()
    except Exception as e:
        return Sample(script, start, (time.perf_counter() - start) * 1000, False,
                      f"{type(e).__name__}: {e}")
    finally:
        try:
            writer.close()
            await writer.wait_closed()
        except Exception:
            pass


def _reply_for(cmd: str, *, script: str, dest: str, cdr_uuid: str) -> str:
    """Mock AGI command replies. Just enough for the handlers to make
    forward progress so we measure realistic latency."""
    cmd_lower = cmd.lower()

    if cmd_lower.startswith("get variable"):
        # Return values for a few specific channel variables the handlers query.
        if "TRUNK_ENDPOINT" in cmd:
            return '200 result=1 (trunk-both-1)'
        if "CDR_UUID" in cmd:
            return f'200 result=1 ({cdr_uuid})'
        if "DIALSTATUS" in cmd:
            return '200 result=1 (CANCEL)'
        if "ANSWEREDTIME" in cmd or "DIALEDTIME" in cmd:
            return '200 result=1 (0)'
        if "HANGUPCAUSE" in cmd:
            return '200 result=1 (16)'
        if "CHANNEL(pjsip,remote_addr)" in cmd:
            return '200 result=1 (10.0.0.1:5060)'
        # Generic "no value set"
        return '200 result=0'

    # SET VARIABLE / VERBOSE / ANSWER / EXEC / STREAM FILE / GET DATA — accept all
    return '200 result=1'


async def _run(host: str, port: int, rate: float, duration: float,
               mix: list[str], sip_username: str, dest: str,
               cdr_uuids: list[str]) -> list[Sample]:
    samples: list[Sample] = []
    interval = 1.0 / rate
    start = time.perf_counter()
    deadline = start + duration

    pending: set[asyncio.Task] = set()
    next_fire = start
    i = 0

    while time.perf_counter() < deadline or pending:
        now = time.perf_counter()

        # Fire new requests up to the current schedule.
        while now >= next_fire and now < deadline:
            script = mix[i % len(mix)]
            i += 1
            cdr_uuid = random.choice(cdr_uuids) if cdr_uuids else str(uuid.uuid4())
            t = asyncio.create_task(_drive_one(host, port, script, sip_username, dest, cdr_uuid))
            pending.add(t)
            t.add_done_callback(pending.discard)
            next_fire += interval

        # Drain any finished tasks.
        if pending:
            done, _ = await asyncio.wait(
                pending, timeout=max(0.001, min(0.05, next_fire - now)),
                return_when=asyncio.FIRST_COMPLETED,
            )
            for t in done:
                try:
                    samples.append(t.result())
                except Exception as e:
                    samples.append(Sample("?", time.perf_counter(), 0, False, str(e)))
        else:
            # No work in flight and we're past the schedule — sleep until next fire.
            sleep_for = max(0, next_fire - now)
            if sleep_for > 0:
                await asyncio.sleep(min(sleep_for, 0.1))

    return samples


def _percentile(values: list[float], p: float) -> float:
    if not values:
        return 0.0
    s = sorted(values)
    k = (len(s) - 1) * p / 100
    f = int(k)
    c = min(f + 1, len(s) - 1)
    if f == c:
        return s[f]
    return s[f] + (s[c] - s[f]) * (k - f)


def _report(samples: list[Sample], duration: float, rate: float) -> None:
    if not samples:
        print("no samples collected")
        return

    by_script: dict[str, list[Sample]] = {}
    for s in samples:
        by_script.setdefault(s.script, []).append(s)

    print()
    print(f"=== AGI load test report ===")
    print(f"target rate     : {rate:.1f} req/s for {duration:.0f}s")
    print(f"total requests  : {len(samples)}")
    print(f"actual rate     : {len(samples) / duration:.1f} req/s")

    overall_ok = sum(1 for s in samples if s.ok)
    print(f"success rate    : {overall_ok}/{len(samples)} ({overall_ok / len(samples) * 100:.1f}%)")

    for script, group in by_script.items():
        latencies = [s.latency_ms for s in group if s.ok]
        errs = [s.error for s in group if not s.ok]
        print()
        print(f"  -- {script} ({len(group)} req) --")
        if latencies:
            print(f"  p50 latency    : {_percentile(latencies, 50):.1f} ms")
            print(f"  p95 latency    : {_percentile(latencies, 95):.1f} ms")
            print(f"  p99 latency    : {_percentile(latencies, 99):.1f} ms")
            print(f"  max latency    : {max(latencies):.1f} ms")
            print(f"  mean latency   : {statistics.fmean(latencies):.1f} ms")
        if errs:
            from collections import Counter
            top = Counter(errs).most_common(5)
            print(f"  errors         : {sum(1 for _ in errs)}")
            for e, n in top:
                print(f"      {n:>5}  {e}")


def main() -> None:
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--target", default="127.0.0.1:4573",
                   help="host:port of FastAGI server (default 127.0.0.1:4573)")
    p.add_argument("--rate", type=float, required=True,
                   help="target requests per second")
    p.add_argument("--duration", type=float, default=60,
                   help="test duration in seconds (default 60)")
    p.add_argument("--mix", default="outbound",
                   help="comma-separated scripts to drive: outbound,call_end (default outbound)")
    p.add_argument("--sip-username", default="09647000006",
                   help="SIP username to put in PJSIP channel (default 09647000006)")
    p.add_argument("--dest", default="8801712345678",
                   help="dialled number for outbound flow (default 8801712345678)")
    p.add_argument("--cdr-uuids", default="",
                   help="comma-separated CDR uuids for call_end flow (omit to use random)")
    args = p.parse_args()

    host, _, port_s = args.target.partition(":")
    port = int(port_s or 4573)

    script_map = {"outbound": "route_outbound", "call_end": "call_end"}
    mix = [script_map[s.strip()] for s in args.mix.split(",") if s.strip()]
    if not mix:
        raise SystemExit("--mix must include at least one of: outbound, call_end")

    cdr_uuids = [u.strip() for u in args.cdr_uuids.split(",") if u.strip()]

    print(f"driving {host}:{port} at {args.rate} req/s for {args.duration}s — mix={mix}")
    samples = asyncio.run(_run(
        host=host, port=port, rate=args.rate, duration=args.duration,
        mix=mix, sip_username=args.sip_username, dest=args.dest,
        cdr_uuids=cdr_uuids,
    ))
    _report(samples, args.duration, args.rate)


if __name__ == "__main__":
    main()
