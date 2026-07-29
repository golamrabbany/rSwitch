# Per-Leg RTP QoS Capture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Record RTP packet counts, loss, jitter and RTT for both legs of every outbound call onto the CDR, so no-audio and one-way-audio calls become detectable and attributable.

**Architecture:** Each leg reports its own `CHANNEL(rtpqos,audio,…)` from a hangup handler, because those values vanish when the channel is destroyed. The customer leg piggybacks on the existing `call_end` AGI call. The trunk leg gets a hangup handler pushed by the `[set-jb]` pre-dial subroutine that already runs on it, and reports to a **new** `leg_qos` AGI handler that writes only trunk columns.

**Tech Stack:** Laravel 12 migration, Asterisk 21 dialplan (`extensions.conf`), Python 3 FastAGI handlers (SQLAlchemy Core `text()`), Blade + PHP for the report.

**Spec:** `docs/superpowers/specs/2026-07-29-rtp-qos-capture-design.md`

## Global Constraints

- **`NULL` ≠ `0`.** `NULL` means "not captured"; `0` means "no packets arrived". The no-audio detector depends on this. Never default a QoS column to `0`, and never coerce an empty AGI variable to `0`.
- **`leg_qos` must never write** `disposition`, `duration`, `billsec`, `status`, `total_cost`, `reseller_cost` or `trunk_cost`. It updates only the six `rtp_trunk_*` columns.
- **Capture failures must be silent to the call and visible in logs.** Log at `logger.warning` or higher — never `logger.debug`. (A `logger.debug` on a failing DB write hid the `last_registered_at` bug for months.)
- **Scope is outbound (`sip_to_trunk`) only.** Do not modify the inbound or internal `Dial` lines.
- **`call_records` is partitioned** — additive `ADD COLUMN` only; never `MODIFY`/`DROP` in this work.
- The **production venv has no pytest** (`/var/www/rswitch/python-services/venv`). Python tests run locally; server-side verification uses a standalone script invoked with `venv/bin/python`.
- Asterisk rtpqos field names are exactly: `rxcount`, `txcount`, `rxploss`, `txploss`, `rxjitter`, `rtt`.

---

### Task 1: Migration — add the 12 RTP QoS columns

**Files:**
- Create: `database/migrations/2026_07_29_120000_add_rtp_qos_to_call_records_table.php`

**Interfaces:**
- Consumes: nothing.
- Produces: columns `rtp_cust_rx_count`, `rtp_cust_tx_count`, `rtp_cust_rx_loss`, `rtp_cust_tx_loss`, `rtp_cust_rx_jitter`, `rtp_cust_rtt`, `rtp_trunk_rx_count`, `rtp_trunk_tx_count`, `rtp_trunk_rx_loss`, `rtp_trunk_tx_loss`, `rtp_trunk_rx_jitter`, `rtp_trunk_rtt` on `call_records`. Every later task writes or reads these exact names.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-leg RTP quality, captured at hangup from CHANNEL(rtpqos,audio,...).
     *
     * All columns are nullable on purpose: NULL means "not captured" (rows from
     * before this migration, or a leg that never came up), while 0 means "no
     * packets arrived". That distinction is the no-audio signal itself, so these
     * must never be given a 0 default.
     *
     * call_records is partitioned by call_start; ADD COLUMN is safe here (the
     * same operation was used for origin_sip_account_id).
     */
    public function up(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            // Customer leg: Asterisk <-> the client's phone
            $table->unsignedInteger('rtp_cust_rx_count')->nullable()->after('trunk_cost');
            $table->unsignedInteger('rtp_cust_tx_count')->nullable()->after('rtp_cust_rx_count');
            $table->unsignedInteger('rtp_cust_rx_loss')->nullable()->after('rtp_cust_tx_count');
            $table->unsignedInteger('rtp_cust_tx_loss')->nullable()->after('rtp_cust_rx_loss');
            $table->decimal('rtp_cust_rx_jitter', 8, 3)->nullable()->after('rtp_cust_tx_loss');
            $table->decimal('rtp_cust_rtt', 8, 3)->nullable()->after('rtp_cust_rx_jitter');

            // Carrier leg: Asterisk <-> the trunk
            $table->unsignedInteger('rtp_trunk_rx_count')->nullable()->after('rtp_cust_rtt');
            $table->unsignedInteger('rtp_trunk_tx_count')->nullable()->after('rtp_trunk_rx_count');
            $table->unsignedInteger('rtp_trunk_rx_loss')->nullable()->after('rtp_trunk_tx_count');
            $table->unsignedInteger('rtp_trunk_tx_loss')->nullable()->after('rtp_trunk_rx_loss');
            $table->decimal('rtp_trunk_rx_jitter', 8, 3)->nullable()->after('rtp_trunk_tx_loss');
            $table->decimal('rtp_trunk_rtt', 8, 3)->nullable()->after('rtp_trunk_rx_jitter');
        });
    }

    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->dropColumn([
                'rtp_cust_rx_count', 'rtp_cust_tx_count', 'rtp_cust_rx_loss',
                'rtp_cust_tx_loss', 'rtp_cust_rx_jitter', 'rtp_cust_rtt',
                'rtp_trunk_rx_count', 'rtp_trunk_tx_count', 'rtp_trunk_rx_loss',
                'rtp_trunk_tx_loss', 'rtp_trunk_rx_jitter', 'rtp_trunk_rtt',
            ]);
        });
    }
};
```

- [ ] **Step 2: Verify the file parses**

Run: `php -l database/migrations/2026_07_29_120000_add_rtp_qos_to_call_records_table.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Add the columns to the CallRecord model fillable list**

In `app/Models/CallRecord.php`, append to the `$fillable` array (it already contains `'disposition', 'hangup_cause', 'status'`):

```php
        'rtp_cust_rx_count', 'rtp_cust_tx_count', 'rtp_cust_rx_loss',
        'rtp_cust_tx_loss', 'rtp_cust_rx_jitter', 'rtp_cust_rtt',
        'rtp_trunk_rx_count', 'rtp_trunk_tx_count', 'rtp_trunk_rx_loss',
        'rtp_trunk_tx_loss', 'rtp_trunk_rx_jitter', 'rtp_trunk_rtt',
```

- [ ] **Step 4: Verify the model parses**

Run: `php -l app/Models/CallRecord.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_29_120000_add_rtp_qos_to_call_records_table.php app/Models/CallRecord.php
git commit -m "feat(cdr): add per-leg RTP QoS columns to call_records"
```

---

### Task 2: Shared RTP value parser

**Files:**
- Create: `python-services/call_control/rtp_qos.py`
- Test: `python-services/tests/test_rtp_qos.py`

**Interfaces:**
- Consumes: `AgiConnection` from `call_control.agi_protocol` (has `async get_variable(name) -> str | None`).
- Produces:
  - `RTP_COLUMN_SUFFIXES: tuple[str, ...]` = `("rx_count", "tx_count", "rx_loss", "tx_loss", "rx_jitter", "rtt")`
  - `parse_count(raw: str | None) -> int | None`
  - `parse_seconds(raw: str | None) -> Decimal | None`
  - `async read_rtp_qos(agi, prefix: str) -> dict[str, int | Decimal | None]` — reads `RTP_{PREFIX}_{FIELD}` AGI variables and returns a dict keyed by the six suffixes above. Tasks 3 and 4 both call this.

- [ ] **Step 1: Write the failing test**

```python
"""RTP QoS value parsing.

NULL and 0 mean different things: NULL is "not captured", 0 is "no packets
arrived". Conflating them destroys the no-audio detector, so every parse of an
absent or unusable value must yield None, never 0.
"""

from decimal import Decimal

import pytest

from call_control.rtp_qos import RTP_COLUMN_SUFFIXES, parse_count, parse_seconds, read_rtp_qos


def test_parse_count_reads_digits():
    assert parse_count("1490") == 1490


def test_parse_count_keeps_zero_distinct_from_missing():
    assert parse_count("0") == 0          # no packets arrived -- a real finding
    assert parse_count("") is None        # not captured
    assert parse_count(None) is None
    assert parse_count("(null)") is None  # Asterisk's empty-variable rendering


def test_parse_count_rejects_garbage():
    assert parse_count("abc") is None
    assert parse_count("-5") is None      # counters cannot be negative


def test_parse_seconds_reads_decimal():
    assert parse_seconds("0.012") == Decimal("0.012")
    assert parse_seconds("0") == Decimal("0")


def test_parse_seconds_keeps_missing_as_none():
    assert parse_seconds("") is None
    assert parse_seconds(None) is None
    assert parse_seconds("nan") is None   # Asterisk emits nan for unqualified peers


class _FakeAgi:
    def __init__(self, values):
        self.values = values
        self.asked = []

    async def get_variable(self, name):
        self.asked.append(name)
        return self.values.get(name)


@pytest.mark.asyncio
async def test_read_rtp_qos_maps_all_six_fields():
    agi = _FakeAgi({
        "RTP_TRUNK_RXCOUNT": "1490",
        "RTP_TRUNK_TXCOUNT": "1502",
        "RTP_TRUNK_RXPLOSS": "3",
        "RTP_TRUNK_TXPLOSS": "0",
        "RTP_TRUNK_RXJITTER": "0.012",
        "RTP_TRUNK_RTT": "0.107",
    })

    result = await read_rtp_qos(agi, "TRUNK")

    assert set(result) == set(RTP_COLUMN_SUFFIXES)
    assert result["rx_count"] == 1490
    assert result["tx_count"] == 1502
    assert result["rx_loss"] == 3
    assert result["tx_loss"] == 0
    assert result["rx_jitter"] == Decimal("0.012")
    assert result["rtt"] == Decimal("0.107")


@pytest.mark.asyncio
async def test_read_rtp_qos_returns_none_for_absent_variables():
    result = await read_rtp_qos(_FakeAgi({}), "CUST")
    assert set(result) == set(RTP_COLUMN_SUFFIXES)
    assert all(v is None for v in result.values())
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd python-services && python -m pytest tests/test_rtp_qos.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'call_control.rtp_qos'`

- [ ] **Step 3: Write the implementation**

```python
"""Per-leg RTP quality values, read from AGI variables set in the dialplan.

Asterisk exposes these through CHANNEL(rtpqos,audio,<field>), which is only
readable while the channel still exists -- hence the dialplan captures them into
variables inside a hangup handler and we read them here.

NULL vs 0 is load-bearing. NULL means the value was never captured (the leg never
came up, or the dialplan did not run); 0 means the counter really was zero, which
is exactly the no-audio signal. Anything unparseable becomes None so it cannot be
mistaken for a genuine zero.
"""

import logging
from decimal import Decimal, InvalidOperation

logger = logging.getLogger(__name__)

# DB column suffix -> Asterisk rtpqos field name.
_FIELD_MAP = (
    ("rx_count", "RXCOUNT"),
    ("tx_count", "TXCOUNT"),
    ("rx_loss", "RXPLOSS"),
    ("tx_loss", "TXPLOSS"),
    ("rx_jitter", "RXJITTER"),
    ("rtt", "RTT"),
)

RTP_COLUMN_SUFFIXES = tuple(suffix for suffix, _ in _FIELD_MAP)

_COUNT_SUFFIXES = {"rx_count", "tx_count", "rx_loss", "tx_loss"}

# Asterisk renders an unset variable in a few ways depending on context.
_EMPTY = {"", "(null)", "unknown", "nan"}


def _clean(raw):
    if raw is None:
        return None
    value = raw.strip()
    return None if value.lower() in _EMPTY else value


def parse_count(raw):
    """Packet/loss counter -> int, or None when absent or unusable."""
    value = _clean(raw)
    if value is None or not value.isdigit():
        return None
    return int(value)


def parse_seconds(raw):
    """Jitter/RTT in seconds -> Decimal, or None when absent or unusable."""
    value = _clean(raw)
    if value is None:
        return None
    try:
        parsed = Decimal(value)
    except (InvalidOperation, ValueError):
        return None
    return None if parsed < 0 else parsed


async def read_rtp_qos(agi, prefix: str) -> dict:
    """Read RTP_{prefix}_* AGI variables for one leg.

    Returns a dict keyed by RTP_COLUMN_SUFFIXES, with None for anything the
    dialplan did not provide. Never raises -- a leg with no stats must not break
    call teardown.
    """
    result = {}
    for suffix, field in _FIELD_MAP:
        try:
            raw = await agi.get_variable(f"RTP_{prefix}_{field}")
        except Exception as e:
            logger.warning(f"RTP QoS: could not read RTP_{prefix}_{field}: {e}")
            raw = None
        result[suffix] = (
            parse_count(raw) if suffix in _COUNT_SUFFIXES else parse_seconds(raw)
        )
    return result
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd python-services && python -m pytest tests/test_rtp_qos.py -v`
Expected: PASS — 7 passed

- [ ] **Step 5: Commit**

```bash
git add python-services/call_control/rtp_qos.py python-services/tests/test_rtp_qos.py
git commit -m "feat(engine): add RTP QoS value parser preserving NULL vs zero"
```

---

### Task 3: Store the customer leg in `call_end`

**Files:**
- Modify: `python-services/call_control/call_end_handler.py` (the `UPDATE call_records` in `_process`, ~lines 148-168)
- Test: `python-services/tests/test_call_end_rtp.py`

**Interfaces:**
- Consumes: `read_rtp_qos(agi, "CUST")` from Task 2; the six `rtp_cust_*` columns from Task 1.
- Produces: nothing later tasks depend on.

- [ ] **Step 1: Write the failing test**

```python
"""call_end must fold the customer-leg RTP values into its existing UPDATE.

It must not issue a second statement (the point of piggybacking is to avoid an
extra round-trip), and it must never send 0 where the value was absent.
"""

import re

from call_control import call_end_handler


def test_update_statement_includes_all_six_cust_columns():
    source = open(call_end_handler.__file__).read()
    stmt = re.search(r"UPDATE call_records SET(.+?)WHERE uuid", source, re.S).group(1)
    for column in (
        "rtp_cust_rx_count", "rtp_cust_tx_count", "rtp_cust_rx_loss",
        "rtp_cust_tx_loss", "rtp_cust_rx_jitter", "rtp_cust_rtt",
    ):
        assert column in stmt, f"{column} missing from the call_end UPDATE"


def test_call_end_does_not_write_trunk_columns():
    """The trunk leg is leg_qos's job; call_end must stay out of it."""
    source = open(call_end_handler.__file__).read()
    assert "rtp_trunk_" not in source


def test_call_end_issues_only_one_update():
    source = open(call_end_handler.__file__).read()
    assert source.count("UPDATE call_records SET") == 1
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd python-services && python -m pytest tests/test_call_end_rtp.py -v`
Expected: FAIL on `test_update_statement_includes_all_six_cust_columns` — "rtp_cust_rx_count missing from the call_end UPDATE"

- [ ] **Step 3: Add the import**

At the top of `python-services/call_control/call_end_handler.py`, beside the existing `from call_control.agi_protocol import AgiConnection`:

```python
from call_control.rtp_qos import read_rtp_qos
```

- [ ] **Step 4: Read the values and extend the UPDATE**

Immediately before the `# 5. Update CDR` comment, add:

```python
        # Customer-leg RTP quality. Captured by [hangup-handler] into RTP_CUST_*
        # before it called us, so it rides along on this UPDATE rather than
        # costing a second AGI round-trip.
        cust = await read_rtp_qos(agi, "CUST")
```

Then replace the `UPDATE` statement and its parameter dict with:

```python
        session.execute(
            text("""
                UPDATE call_records SET
                    call_end = NOW(),
                    duration = :duration,
                    billsec = :billsec,
                    disposition = :disposition,
                    hangup_cause = :hangup_cause,
                    status = :status,
                    rtp_cust_rx_count = :cust_rx_count,
                    rtp_cust_tx_count = :cust_tx_count,
                    rtp_cust_rx_loss = :cust_rx_loss,
                    rtp_cust_tx_loss = :cust_tx_loss,
                    rtp_cust_rx_jitter = :cust_rx_jitter,
                    rtp_cust_rtt = :cust_rtt
                WHERE uuid = :uuid
            """),
            {
                "uuid": cdr_uuid,
                "duration": duration,
                "billsec": billsec,
                "disposition": disposition,
                "hangup_cause": hangup_cause,
                "status": status,
                "cust_rx_count": cust["rx_count"],
                "cust_tx_count": cust["tx_count"],
                "cust_rx_loss": cust["rx_loss"],
                "cust_tx_loss": cust["tx_loss"],
                "cust_rx_jitter": cust["rx_jitter"],
                "cust_rtt": cust["rtt"],
            },
        )
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `cd python-services && python -m pytest tests/test_call_end_rtp.py tests/test_rtp_qos.py -v`
Expected: PASS — all tests pass

- [ ] **Step 6: Commit**

```bash
git add python-services/call_control/call_end_handler.py python-services/tests/test_call_end_rtp.py
git commit -m "feat(engine): record customer-leg RTP QoS in the call_end CDR update"
```

---

### Task 4: `leg_qos` handler for the trunk leg

**Files:**
- Create: `python-services/call_control/leg_qos_handler.py`
- Modify: `python-services/call_control/agi_server.py` (imports, handler instances ~line 28-32, dispatch chain ~line 60-71)
- Test: `python-services/tests/test_leg_qos_handler.py`

**Interfaces:**
- Consumes: `read_rtp_qos(agi, "TRUNK")` from Task 2; the six `rtp_trunk_*` columns from Task 1.
- Produces: class `LegQosHandler` with `async handle(self, agi: AgiConnection, session: Session) -> None`, registered under the AGI script name `leg_qos`. Task 5's dialplan calls `agi://${AGI_HOST}:${AGI_PORT}/leg_qos`.

- [ ] **Step 1: Write the failing test**

```python
"""The trunk-leg handler must be structurally incapable of touching billing."""

import re

import pytest

from call_control import leg_qos_handler
from call_control.leg_qos_handler import LegQosHandler

FORBIDDEN = (
    "disposition", "duration", "billsec", "status",
    "total_cost", "reseller_cost", "trunk_cost",
)


def test_updates_only_trunk_columns():
    source = open(leg_qos_handler.__file__).read()
    stmt = re.search(r"UPDATE call_records SET(.+?)WHERE uuid", source, re.S).group(1)
    for column in (
        "rtp_trunk_rx_count", "rtp_trunk_tx_count", "rtp_trunk_rx_loss",
        "rtp_trunk_tx_loss", "rtp_trunk_rx_jitter", "rtp_trunk_rtt",
    ):
        assert column in stmt
    for forbidden in FORBIDDEN:
        assert forbidden not in stmt, f"leg_qos must never write {forbidden}"


class _FakeAgi:
    def __init__(self, values):
        self.values = values
        self.verbose_calls = []

    async def get_variable(self, name):
        return self.values.get(name)

    async def verbose(self, message):
        self.verbose_calls.append(message)


class _FakeSession:
    def __init__(self):
        self.executed = []
        self.committed = False

    def execute(self, stmt, params=None):
        self.executed.append(params)

    def commit(self):
        self.committed = True


@pytest.mark.asyncio
async def test_no_cdr_uuid_is_a_noop():
    session = _FakeSession()
    await LegQosHandler().handle(_FakeAgi({}), session)
    assert session.executed == []
    assert session.committed is False


@pytest.mark.asyncio
async def test_writes_parsed_values_for_the_trunk_leg():
    agi = _FakeAgi({
        "CDR_UUID": "abc-123",
        "RTP_TRUNK_RXCOUNT": "0",
        "RTP_TRUNK_TXCOUNT": "1502",
    })
    session = _FakeSession()

    await LegQosHandler().handle(agi, session)

    assert session.committed is True
    params = session.executed[0]
    assert params["uuid"] == "abc-123"
    assert params["trunk_rx_count"] == 0     # real zero: the no-audio signal
    assert params["trunk_tx_count"] == 1502
    assert params["trunk_rx_loss"] is None   # absent, not zero


@pytest.mark.asyncio
async def test_database_error_is_swallowed_not_raised():
    """The call is already over; a stats failure must never propagate."""
    class _Boom(_FakeSession):
        def execute(self, stmt, params=None):
            raise RuntimeError("db gone")

    await LegQosHandler().handle(_FakeAgi({"CDR_UUID": "abc-123"}), _Boom())
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd python-services && python -m pytest tests/test_leg_qos_handler.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'call_control.leg_qos_handler'`

- [ ] **Step 3: Write the handler**

```python
"""Trunk-leg RTP QoS -> CDR.

Runs from the [leg-qos] hangup handler that [set-jb] pushes onto the callee
(trunk) channel at pre-dial time. Deliberately separate from CallEndHandler: this
path updates only the six rtp_trunk_* columns and must never be able to alter
disposition, duration, billsec, status or any cost column.

The call is already over by the time this runs, so every failure is swallowed --
but logged at warning, never debug.
"""

import logging

from sqlalchemy import text
from sqlalchemy.orm import Session

from call_control.agi_protocol import AgiConnection
from call_control.rtp_qos import read_rtp_qos

logger = logging.getLogger(__name__)


class LegQosHandler:
    """Records the carrier-leg RTP quality for a completed call."""

    async def handle(self, agi: AgiConnection, session: Session) -> None:
        try:
            await self._process(agi, session)
        except Exception as e:
            logger.warning(f"LegQos handler error: {e}", exc_info=True)

    async def _process(self, agi: AgiConnection, session: Session) -> None:
        cdr_uuid = await agi.get_variable("CDR_UUID")
        if not cdr_uuid:
            # Expected when the trunk leg never carried a CDR (e.g. rejected
            # before Dial). Nothing to attach stats to.
            return

        trunk = await read_rtp_qos(agi, "TRUNK")

        session.execute(
            text("""
                UPDATE call_records SET
                    rtp_trunk_rx_count = :trunk_rx_count,
                    rtp_trunk_tx_count = :trunk_tx_count,
                    rtp_trunk_rx_loss = :trunk_rx_loss,
                    rtp_trunk_tx_loss = :trunk_tx_loss,
                    rtp_trunk_rx_jitter = :trunk_rx_jitter,
                    rtp_trunk_rtt = :trunk_rtt
                WHERE uuid = :uuid
            """),
            {
                "uuid": cdr_uuid,
                "trunk_rx_count": trunk["rx_count"],
                "trunk_tx_count": trunk["tx_count"],
                "trunk_rx_loss": trunk["rx_loss"],
                "trunk_tx_loss": trunk["tx_loss"],
                "trunk_rx_jitter": trunk["rx_jitter"],
                "trunk_rtt": trunk["rtt"],
            },
        )
        session.commit()

        logger.info(
            f"CDR {cdr_uuid}: trunk RTP rx={trunk['rx_count']} tx={trunk['tx_count']} "
            f"rxloss={trunk['rx_loss']} jitter={trunk['rx_jitter']}"
        )
```

- [ ] **Step 4: Register the handler in the AGI dispatcher**

In `python-services/call_control/agi_server.py`:

Add the import beside the other handler imports:

```python
from call_control.leg_qos_handler import LegQosHandler
```

Add the instance beside `_forward = ForwardCallHandler()`:

```python
_leg_qos = LegQosHandler()
```

Add the branch to the dispatch chain, after the `forward_call` branch and before the `else`:

```python
                elif script == "leg_qos":
                    await _leg_qos.handle(conn, session)
```

Also add `- leg_qos       → LegQosHandler` to the `Scripts:` list in the module docstring.

- [ ] **Step 5: Run tests to verify they pass**

Run: `cd python-services && python -m pytest tests/test_leg_qos_handler.py tests/test_rtp_qos.py tests/test_call_end_rtp.py -v`
Expected: PASS — all tests pass

- [ ] **Step 6: Verify both files parse**

Run: `python3 -m py_compile python-services/call_control/leg_qos_handler.py python-services/call_control/agi_server.py`
Expected: no output

- [ ] **Step 7: Commit**

```bash
git add python-services/call_control/leg_qos_handler.py python-services/call_control/agi_server.py python-services/tests/test_leg_qos_handler.py
git commit -m "feat(engine): add leg_qos AGI handler for carrier-leg RTP stats"
```

---

### Task 5: Dialplan — capture on both legs

**Files:**
- Modify: `/etc/asterisk/extensions.conf` on pacevoice (`123.136.31.91`), and `docker/asterisk/conf/extensions.conf` in the repo so the two stay in step.

**Interfaces:**
- Consumes: the `leg_qos` AGI script name from Task 4.
- Produces: AGI variables `RTP_CUST_{RXCOUNT,TXCOUNT,RXPLOSS,TXPLOSS,RXJITTER,RTT}` on the customer leg and `RTP_TRUNK_*` on the trunk leg, plus an inheritable `CDR_UUID` on child channels.

- [ ] **Step 1: Back up the live dialplan**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "cp -a /etc/asterisk/extensions.conf /etc/asterisk/extensions.conf.bak.rtpqos && \
   md5sum /etc/asterisk/extensions.conf"
```

- [ ] **Step 2: Make `CDR_UUID` inheritable before each trunk Dial**

In `[from-internal]`, insert immediately **before** each of the two trunk `Dial` lines (the primary `Dial(${ROUTE_DIAL_STRING},...,gTb(set-jb^s^1))` and the failover `Dial(${ROUTE_FAILOVER},...,gTb(set-jb^s^1))`):

```
 same => n,Set(__CDR_UUID=${CDR_UUID})
```

The double underscore makes the variable inherit to child channels, where it appears as plain `CDR_UUID`. Without it the trunk leg has no idea which CDR row it belongs to. Do **not** touch the internal `Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)` on the `(internal)` branch — that is sip_to_sip and out of scope.

- [ ] **Step 3: Push the QoS handler onto the trunk leg**

Replace the `[set-jb]` context entirely with:

```
[set-jb]
; Runs on the callee (trunk) leg via Dial(...,b(set-jb^s^1)) before the carrier is called.
; Adaptive jitter buffer + PLC to smooth choppy audio the customer hears.
exten => s,1,Set(JITTERBUFFER(adaptive)=default)
; Capture this leg's RTP quality when it hangs up. CHANNEL(rtpqos,...) is only
; readable while the channel exists, so it has to happen in a hangup handler.
 same => n,Set(CHANNEL(hangup_handler_push)=leg-qos,s,1)
 same => n,Return()
```

- [ ] **Step 4: Add the `[leg-qos]` context**

Append after the `[hangup-handler]` context:

```
; --- Context: Carrier-leg RTP quality capture (pushed by [set-jb]) ---
[leg-qos]
exten => s,1,NoOp(Trunk-leg RTP QoS — CDR UUID: ${CDR_UUID})
 same => n,GotoIf($["${CDR_UUID}" = ""]?done)
 same => n,Set(RTP_TRUNK_RXCOUNT=${CHANNEL(rtpqos,audio,rxcount)})
 same => n,Set(RTP_TRUNK_TXCOUNT=${CHANNEL(rtpqos,audio,txcount)})
 same => n,Set(RTP_TRUNK_RXPLOSS=${CHANNEL(rtpqos,audio,rxploss)})
 same => n,Set(RTP_TRUNK_TXPLOSS=${CHANNEL(rtpqos,audio,txploss)})
 same => n,Set(RTP_TRUNK_RXJITTER=${CHANNEL(rtpqos,audio,rxjitter)})
 same => n,Set(RTP_TRUNK_RTT=${CHANNEL(rtpqos,audio,rtt)})
 same => n,AGI(agi://${AGI_HOST}:${AGI_PORT}/leg_qos)
 same => n(done),Return()
```

- [ ] **Step 5: Capture the customer leg in the existing hangup handler**

Replace the `[hangup-handler]` context with:

```
; --- Context: Hangup handler for CDR finalization ---
[hangup-handler]
exten => s,1,NoOp(Hangup handler — CDR UUID: ${CDR_UUID})
 same => n,GotoIf($["${CDR_UUID}" = ""]?done)
 same => n,Set(CALL_DURATION=${CDR(duration)})
 same => n,Set(CALL_BILLSEC=${CDR(billsec)})
 same => n,Set(RTP_CUST_RXCOUNT=${CHANNEL(rtpqos,audio,rxcount)})
 same => n,Set(RTP_CUST_TXCOUNT=${CHANNEL(rtpqos,audio,txcount)})
 same => n,Set(RTP_CUST_RXPLOSS=${CHANNEL(rtpqos,audio,rxploss)})
 same => n,Set(RTP_CUST_TXPLOSS=${CHANNEL(rtpqos,audio,txploss)})
 same => n,Set(RTP_CUST_RXJITTER=${CHANNEL(rtpqos,audio,rxjitter)})
 same => n,Set(RTP_CUST_RTT=${CHANNEL(rtpqos,audio,rtt)})
 same => n,AGI(agi://${AGI_HOST}:${AGI_PORT}/call_end)
 same => n(done),Return()
```

- [ ] **Step 6: Commit the repo copy**

```bash
git add docker/asterisk/conf/extensions.conf
git commit -m "feat(dialplan): capture per-leg RTP QoS at hangup"
```

---

### Task 6: Deploy and verify against live traffic

**Files:** none created — this task deploys Tasks 1-5 and proves them.

**Interfaces:**
- Consumes: everything from Tasks 1-5.
- Produces: populated QoS columns on live CDRs, which Task 7 reports on.

- [ ] **Step 1: Run the migration**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "cd /var/www/rswitch && sudo -u www-data php8.3 artisan migrate --force"
```

Expected: the `add_rtp_qos_to_call_records_table` migration runs. Verify:

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -N -e \
   \"SHOW COLUMNS FROM call_records;\" | awk '{print \$1}' | grep -c '^rtp_'"
```

Expected: `12`

- [ ] **Step 2: Deploy the engine files**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') scp -o StrictHostKeyChecking=no \
  python-services/call_control/rtp_qos.py \
  python-services/call_control/leg_qos_handler.py \
  python-services/call_control/call_end_handler.py \
  python-services/call_control/agi_server.py \
  root@123.136.31.91:/tmp/

sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
set -e
cd /var/www/rswitch/python-services
for f in rtp_qos.py leg_qos_handler.py call_end_handler.py agi_server.py; do
  venv/bin/python -m py_compile /tmp/$f
  [ -f call_control/$f ] && cp -a call_control/$f call_control/$f.bak.rtpqos
  cp /tmp/$f call_control/$f
  chown www-data:www-data call_control/$f && chmod 644 call_control/$f
  cp /tmp/$f /opt/rswitch-src/python-services/call_control/$f
done
echo deployed'
```

- [ ] **Step 3: Restart the engine and reload the dialplan**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
asterisk -rx "dialplan reload"
asterisk -rx "dialplan show leg-qos" | head -5
supervisorctl restart rswitch-api
sleep 8
supervisorctl status rswitch-api
ss -lntp | grep -E ":4573|:8001" | awk "{print \$1, \$4}"'
```

Expected: `[leg-qos]` context listed, `rswitch-api RUNNING`, both ports listening. The restart costs ~1-2s; established calls are unaffected.

- [ ] **Step 4: Verify capture on live traffic**

Wait ~2 minutes (traffic is ~1.2 calls/sec), then:

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -e \"
   SELECT COUNT(*) completed,
          SUM(rtp_cust_rx_count IS NOT NULL) cust_captured,
          SUM(rtp_trunk_rx_count IS NOT NULL) trunk_captured
   FROM call_records
   WHERE call_start >= NOW() - INTERVAL 5 MINUTE AND status <> 'in_progress';\""
```

Expected: `cust_captured` and `trunk_captured` both close to `completed` (>95% per the spec's success criteria).

- [ ] **Step 5: Sanity-check the packet arithmetic on a healthy call**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -e \"
   SELECT billsec, rtp_trunk_rx_count, ROUND(rtp_trunk_rx_count/NULLIF(billsec,0),1) pkts_per_sec
   FROM call_records
   WHERE call_start >= NOW() - INTERVAL 10 MINUTE AND billsec > 30
     AND rtp_trunk_rx_count > 0 LIMIT 5;\""
```

Expected: `pkts_per_sec` around **50** on healthy calls. A wildly different figure means the ptime assumption in the detection rules needs revisiting before Task 7.

- [ ] **Step 6: Confirm billing is untouched**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -e \"
   SELECT status, COUNT(*) c, ROUND(SUM(total_cost),2) charged
   FROM call_records WHERE call_start >= NOW() - INTERVAL 10 MINUTE GROUP BY status;\"
   grep -c 'LegQos handler error' /var/log/rswitch-python-api.err.log"
```

Expected: the usual `charged` / `unbillable` / `in_progress` split with non-zero `charged`, no unexpected stuck `in_progress`, and `0` LegQos errors.

- [ ] **Step 7: Negative control — unanswered calls must not look like no-audio**

Spec §9.4. A call that never answered has `billsec = 0` and near-zero counts; it
must not be counted as a no-audio fault.

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -e \"
   SELECT SUM(billsec = 0) never_answered,
          SUM(billsec = 0 AND rtp_trunk_rx_count = 0) would_falsely_match_without_billsec_guard
   FROM call_records
   WHERE call_start >= NOW() - INTERVAL 30 MINUTE AND status <> 'in_progress';\""
```

Expected: the second figure is large — which is precisely why every detection
rule carries `billsec > 0`. Confirm the Task 7 filters keep that guard.

- [ ] **Step 8: Run the no-audio query for the first time**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -e \"
   SELECT COUNT(*) no_audio FROM call_records
   WHERE call_start >= NOW() - INTERVAL 1 HOUR
     AND billsec > 0 AND rtp_trunk_rx_count = 0;\""
```

Expected: a number — this is the answer the whole feature exists to produce.

---

### Task 7: No-audio filters on the outbound report

**Files:**
- Modify: `app/Http/Controllers/Admin/OperationalReportController.php` (`outboundCalls`, after the existing "Caller ID filter" block ~line 485)
- Modify: `resources/views/admin/operational-reports/outbound.blade.php` (filter card ~line 95-160)

**Interfaces:**
- Consumes: the `rtp_*` columns from Task 1, populated by Tasks 3-6.
- Produces: an `audio` query parameter accepting `no_audio`, `one_way`, `high_loss`.

**Deliberately not exposed as filters:** spec §5 also defines "audio died
mid-call" (`rx_count < billsec * 25`) and "choppy" (`rx_jitter > 0.030`). Those
stay SQL-only for now — the agreed UI is three tabs, and both rules need
threshold tuning against real data before they are worth surfacing. The raw
columns support them either way, which is the point of storing raw values.

- [ ] **Step 1: Add the filter to the controller**

In `outboundCalls`, immediately after the Caller ID filter block, add:

```php
        // Audio-health filter. Thresholds live here rather than in the schema so
        // they can change without a migration. NULL means "not captured" and is
        // excluded everywhere -- only 0 means "no packets arrived".
        if ($request->filled('audio')) {
            match ($request->audio) {
                // Answered, but the carrier never sent a single packet.
                'no_audio' => $query->where('billsec', '>', 0)
                    ->where('rtp_trunk_rx_count', 0),
                // Audio in one direction only, on either leg.
                'one_way' => $query->where('billsec', '>', 0)
                    ->where(function ($q) {
                        $q->where(fn ($w) => $w->where('rtp_trunk_rx_count', 0)
                                               ->where('rtp_trunk_tx_count', '>', 0))
                          ->orWhere(fn ($w) => $w->where('rtp_trunk_tx_count', 0)
                                                 ->where('rtp_trunk_rx_count', '>', 0))
                          ->orWhere(fn ($w) => $w->where('rtp_cust_rx_count', 0)
                                                 ->where('rtp_cust_tx_count', '>', 0))
                          ->orWhere(fn ($w) => $w->where('rtp_cust_tx_count', 0)
                                                 ->where('rtp_cust_rx_count', '>', 0));
                    }),
                // More than 5% inbound loss on either leg.
                'high_loss' => $query->where('billsec', '>', 0)
                    ->where(function ($q) {
                        $q->whereRaw('rtp_trunk_rx_loss > 0.05 * NULLIF(rtp_trunk_rx_count, 0)')
                          ->orWhereRaw('rtp_cust_rx_loss > 0.05 * NULLIF(rtp_cust_rx_count, 0)');
                    }),
                default => null,
            };
        }
```

- [ ] **Step 2: Verify the controller parses**

Run: `php -l app/Http/Controllers/Admin/OperationalReportController.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Add the filter control to the view**

In `resources/views/admin/operational-reports/outbound.blade.php`, inside the filter form beside the existing `source_ip` / `caller_id` inputs, add:

```blade
                <select name="audio" class="filter-input" style="width:auto;">
                    <option value="">All audio</option>
                    <option value="no_audio" {{ request('audio') === 'no_audio' ? 'selected' : '' }}>No audio</option>
                    <option value="one_way" {{ request('audio') === 'one_way' ? 'selected' : '' }}>One-way</option>
                    <option value="high_loss" {{ request('audio') === 'high_loss' ? 'selected' : '' }}>High loss</option>
                </select>
```

- [ ] **Step 4: Show the packet counts in the table**

In the same file, add a header cell to the table's `<thead>` row:

```blade
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Audio</th>
```

and the matching body cell in the `@foreach` row:

```blade
                        <td class="px-3 py-2 text-xs">
                            @if($call->rtp_trunk_rx_count === null)
                                <span class="text-gray-300">—</span>
                            @elseif($call->billsec > 0 && $call->rtp_trunk_rx_count === 0)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>No audio</span>
                            @else
                                <span class="text-gray-600 tabular-nums">{{ number_format($call->rtp_trunk_rx_count) }}</span>
                            @endif
                        </td>
```

- [ ] **Step 5: Render the view to catch Blade compile errors**

Blade silently mis-compiles some attribute escapes, so render rather than eyeball:

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "cd /var/www/rswitch && sudo -u www-data php8.3 artisan view:clear && \
   sudo -u www-data php8.3 artisan view:cache 2>&1 | tail -3"
```

Expected: no compilation errors.

- [ ] **Step 6: Deploy and verify the filter returns rows**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') scp -o StrictHostKeyChecking=no \
  app/Http/Controllers/Admin/OperationalReportController.php \
  resources/views/admin/operational-reports/outbound.blade.php \
  root@123.136.31.91:/tmp/

sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
cd /var/www/rswitch
cp -a app/Http/Controllers/Admin/OperationalReportController.php app/Http/Controllers/Admin/OperationalReportController.php.bak.audio
cp -a resources/views/admin/operational-reports/outbound.blade.php resources/views/admin/operational-reports/outbound.blade.php.bak.audio
cp /tmp/OperationalReportController.php app/Http/Controllers/Admin/
cp /tmp/outbound.blade.php resources/views/admin/operational-reports/
chown www-data:www-data app/Http/Controllers/Admin/OperationalReportController.php resources/views/admin/operational-reports/outbound.blade.php
sudo -u www-data php8.3 artisan view:clear
sudo -u www-data php8.3 artisan tinker --execute="
\$u = App\Models\User::where(\"role\",\"super_admin\")->first();
auth()->login(\$u);
\$req = Illuminate\Http\Request::create(\"/admin/operational-reports/outbound\",\"GET\",[\"audio\"=>\"no_audio\"]);
app()->instance(\"request\", \$req);
\$c = new App\Http\Controllers\Admin\OperationalReportController();
\$d = \$c->outboundCalls(\$req)->getData();
echo \"no_audio filter rows: \".\$d[\"calls\"]->total().PHP_EOL;
"'
```

Expected: the view renders and reports a row count without error.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/OperationalReportController.php resources/views/admin/operational-reports/outbound.blade.php
git commit -m "feat(reports): filter outbound calls by audio health"
```

---

## Rollback

Each task is independently reversible:

- **Task 7 / 3 / 4** — restore the `.bak.audio` / `.bak.rtpqos` copies on the server, `git revert` the commit.
- **Task 5 (dialplan)** — `cp /etc/asterisk/extensions.conf.bak.rtpqos /etc/asterisk/extensions.conf && asterisk -rx "dialplan reload"`. This is the highest-blast-radius change: a syntax error affects call routing, so verify with `dialplan show from-internal` immediately after reloading.
- **Task 1 (migration)** — `php artisan migrate:rollback --step=1`. Additive columns; dropping them loses only captured stats.

Stopping after Task 6 leaves a fully working feature — the data is captured and queryable by SQL, just without the report UI.
