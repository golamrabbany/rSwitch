# QoS Analysis Report Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Call Quality page to the admin Operational Reports that shows carrier-vs-customer audio quality over time and ranks the worst clients, trunks and destinations.

**Architecture:** Capture Asterisk's own Media Experience Score per leg (already present in the rtcp summary the engine reads and discards), then aggregate the per-leg RTP columns into one report page — summary cards, an hourly Chart.js trend, and a pivotable breakdown table.

**Tech Stack:** Laravel 12 (Eloquent + raw aggregate SQL), Blade, Chart.js 4.4.1 via CDN, Python 3 FastAGI handlers, MySQL 8 partitioned `call_records`.

**Spec:** `docs/superpowers/specs/2026-07-30-qos-analysis-report-design.md`

## Global Constraints

- **`NULL` ≠ `0`.** NULL means "not captured"; 0 means "measured and terrible". Never `COALESCE` a QoS column to 0, never give one a default.
- **Every aggregate query must be bounded by `call_start`** so daily partition pruning works, and the range is capped at **31 days**.
- **Multi-tenant scoping is mandatory:** `if (! $authUser->isSuperAdmin()) { $query->whereIn('user_id', $authUser->descendantIds()); }`. A reseller must never see another reseller's call quality.
- **`rtp_cust_tx_count` must not be surfaced anywhere on this page** — it is known-unreliable (19,083 packets on a 7s call).
- **MES is displayed on its native 0-100 scale.** Do not rescale to a 1-5 MOS.
- **`leg_qos` must never write** `disposition`, `duration`, `billsec`, `status`, `total_cost`, `reseller_cost` or `trunk_cost`.
- **Scope is outbound (`sip_to_trunk`) only.**
- Log capture failures at `logger.warning` or higher — never `logger.debug`.
- `call_records` is partitioned: additive `ADD COLUMN` only.
- Production venv has no pytest; Python tests run locally. Local `php` is 8.2 (MAMP) which cannot run `vendor/bin/phpunit` — use `/opt/homebrew/bin/php` (8.4).

---

### Task 1: Migration — two MES columns

**Files:**
- Create: `database/migrations/2026_07_30_100000_add_rtp_mes_to_call_records_table.php`
- Modify: `app/Models/CallRecord.php`

**Interfaces:**
- Consumes: nothing.
- Produces: columns `rtp_cust_rx_mes`, `rtp_trunk_rx_mes` on `call_records`, cast as `decimal:2`. Tasks 3, 5 and 6 use these exact names.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Media Experience Score per leg, from CHANNEL(rtcp,all,audio).
     *
     * Asterisk already returns rxmes/txmes in the same summary the engine reads
     * for every call -- they were simply discarded. Only the rx score is kept per
     * leg: it measures the audio ARRIVING from the customer phone and from the
     * carrier, which is what a quality investigation asks about. txmes is our own
     * send-side score and answers nothing this report asks.
     *
     * Scale is 0-100, NOT the 1-5 MOS scale. decimal(5,2) fits 0.00-999.99.
     *
     * Nullable with no default, like the twelve RTP columns before them: NULL
     * means "not captured", 0 means "measured and terrible".
     */
    public function up(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->decimal('rtp_cust_rx_mes', 5, 2)->nullable()->after('rtp_cust_rtt');
            $table->decimal('rtp_trunk_rx_mes', 5, 2)->nullable()->after('rtp_trunk_rtt');
        });
    }

    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->dropColumn(['rtp_cust_rx_mes', 'rtp_trunk_rx_mes']);
        });
    }
};
```

- [ ] **Step 2: Verify it parses**

Run: `php -l database/migrations/2026_07_30_100000_add_rtp_mes_to_call_records_table.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Add to `$fillable` and `casts()`**

In `app/Models/CallRecord.php`, append to the `$fillable` array (it already ends with `'rtp_trunk_rx_jitter', 'rtp_trunk_rtt',`):

```php
        'rtp_cust_rx_mes', 'rtp_trunk_rx_mes',
```

and in `casts()`, beside the existing `'rtp_trunk_rtt' => 'decimal:3',`:

```php
            'rtp_cust_rx_mes' => 'decimal:2',
            'rtp_trunk_rx_mes' => 'decimal:2',
```

- [ ] **Step 4: Verify the model parses**

Run: `php -l app/Models/CallRecord.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_30_100000_add_rtp_mes_to_call_records_table.php app/Models/CallRecord.php
git commit -m "feat(cdr): add per-leg Media Experience Score columns"
```

---

### Task 2: Parser — read `rxmes` from the rtcp summary

**Files:**
- Modify: `python-services/call_control/rtp_qos.py`
- Test: `python-services/tests/test_rtp_qos.py`

**Interfaces:**
- Consumes: the existing `_FIELD_MAP`, `parse_count`, `parse_seconds`, `read_rtp_qos(agi, prefix)`.
- Produces: `RTP_COLUMN_SUFFIXES` gains `"rx_mes"` (now 7 suffixes). `read_rtp_qos` returns `rx_mes` as a `Decimal | None`. Task 3 binds it.

- [ ] **Step 1: Write the failing tests**

Append to `python-services/tests/test_rtp_qos.py`:

```python
REAL_SUMMARY_WITH_MES = (
    "ssrc=810533921;themssrc=3758489602;lp=1;rxjitter=0.012000;rxcount=1516;"
    "txjitter=0.000125;txcount=1514;rlp=0;rtt=0.129104;rxmes=83.194000;txmes=82.967221"
)


@pytest.mark.asyncio
async def test_reads_media_experience_score():
    result = await read_rtp_qos(_FakeAgi({"RTP_TRUNK_ALL": REAL_SUMMARY_WITH_MES}), "TRUNK")
    assert result["rx_mes"] == Decimal("83.194000")


@pytest.mark.asyncio
async def test_mes_absent_from_summary_is_none():
    """A summary without rxmes must yield None, not 0 -- 0 means measured-and-terrible."""
    result = await read_rtp_qos(_FakeAgi({"RTP_TRUNK_ALL": "rxcount=100;txcount=100"}), "TRUNK")
    assert result["rx_mes"] is None


@pytest.mark.asyncio
async def test_mes_zero_is_kept_distinct_from_absent():
    result = await read_rtp_qos(_FakeAgi({"RTP_TRUNK_ALL": "rxcount=100;rxmes=0"}), "TRUNK")
    assert result["rx_mes"] == Decimal("0")


def test_rx_mes_is_a_known_suffix():
    assert "rx_mes" in RTP_COLUMN_SUFFIXES
```

Note: `_FakeAgi` already exists in this file from the earlier work — reuse it, do not redefine it.

- [ ] **Step 2: Run to verify they fail**

Run: `cd python-services && python3 -m pytest tests/test_rtp_qos.py -v`
Expected: the three MES value tests fail with `KeyError: 'rx_mes'`, and `test_rx_mes_is_a_known_suffix` fails.

- [ ] **Step 3: Add the field mapping**

In `python-services/call_control/rtp_qos.py`, add one entry to `_FIELD_MAP` (after the `rtt` entry):

```python
_FIELD_MAP = (
    ("rx_count", "rxcount"),
    ("tx_count", "txcount"),
    ("rx_loss", "lp"),
    ("tx_loss", "rlp"),
    ("rx_jitter", "rxjitter"),
    ("rtt", "rtt"),
    # Asterisk's Media Experience Score for the audio we RECEIVED on this leg,
    # 0-100 (not the 1-5 MOS scale). Already present in every rtcp summary.
    ("rx_mes", "rxmes"),
)
```

`rx_mes` is deliberately NOT added to `_COUNT_SUFFIXES`, so it is parsed by `parse_seconds`.

- [ ] **Step 4: Widen the `parse_seconds` docstring**

Its name says seconds but it is now also parsing a 0-100 score. Replace its docstring:

```python
def parse_seconds(raw):
    """Non-negative decimal -> Decimal, or None when absent or unusable.

    Used for jitter and RTT (seconds) and for the Media Experience Score (0-100).
    The name is historical; what it really guarantees is "a finite, non-negative
    decimal, or None". The NaN/infinity guards below exist because a signed NaN
    from Asterisk once crashed call teardown -- do not remove them, and do not
    write a second parser that lacks them.
    """
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `cd python-services && python3 -m pytest tests/test_rtp_qos.py -v`
Expected: PASS, all tests including the four new ones.

- [ ] **Step 6: Commit**

```bash
git add python-services/call_control/rtp_qos.py python-services/tests/test_rtp_qos.py
git commit -m "feat(engine): parse Media Experience Score from the rtcp summary"
```

---

### Task 3: Both handlers store the MES

**Files:**
- Modify: `python-services/call_control/call_end_handler.py`
- Modify: `python-services/call_control/leg_qos_handler.py`
- Test: `python-services/tests/test_call_end_rtp.py`
- Test: `python-services/tests/test_leg_qos_handler.py`

**Interfaces:**
- Consumes: `read_rtp_qos` returning `rx_mes` (Task 2); columns `rtp_cust_rx_mes` / `rtp_trunk_rx_mes` (Task 1).
- Produces: both columns populated on live calls. Tasks 5 and 6 read them.

- [ ] **Step 1: Write the failing tests**

Append to `python-services/tests/test_call_end_rtp.py`:

```python
def test_update_includes_the_cust_mes_column():
    source = open(call_end_handler.__file__).read()
    stmt = re.search(r"UPDATE call_records SET(.+?)WHERE uuid", source, re.S).group(1)
    assert "rtp_cust_rx_mes" in stmt
```

Append to `python-services/tests/test_leg_qos_handler.py`:

```python
def test_update_includes_the_trunk_mes_column():
    source = open(leg_qos_handler.__file__).read()
    stmt = re.search(r"UPDATE call_records SET(.+?)WHERE uuid", source, re.S).group(1)
    assert "rtp_trunk_rx_mes" in stmt
    # still no billing column
    for forbidden in ("disposition", "duration", "billsec", "status", "total_cost"):
        assert forbidden not in stmt
```

- [ ] **Step 2: Run to verify they fail**

Run: `cd python-services && python3 -m pytest tests/test_call_end_rtp.py tests/test_leg_qos_handler.py -v`
Expected: both new tests fail — `assert 'rtp_cust_rx_mes' in stmt` / `assert 'rtp_trunk_rx_mes' in stmt`.

- [ ] **Step 3: Add the column to `call_end_handler.py`**

In the `UPDATE call_records SET` statement, after the `rtp_cust_rtt = :cust_rtt` line, add:

```
                    rtp_cust_rx_mes = :cust_rx_mes,
```

(note: `rtp_cust_rtt = :cust_rtt` must gain a trailing comma), and in the parameter dict after `"cust_rtt": cust["rtt"],` add:

```python
                "cust_rx_mes": cust["rx_mes"],
```

- [ ] **Step 4: Add the column to `leg_qos_handler.py`**

In its `UPDATE call_records SET` statement, after `rtp_trunk_rtt = :trunk_rtt` add:

```
                    rtp_trunk_rx_mes = :trunk_rx_mes
```

(`rtp_trunk_rtt = :trunk_rtt` gains a trailing comma), and in the parameter dict after `"trunk_rtt": trunk["rtt"],` add:

```python
                "trunk_rx_mes": trunk["rx_mes"],
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `cd python-services && python3 -m pytest tests/test_rtp_qos.py tests/test_call_end_rtp.py tests/test_leg_qos_handler.py -v`
Expected: PASS — all three files green.

- [ ] **Step 6: Commit**

```bash
git add python-services/call_control/call_end_handler.py python-services/call_control/leg_qos_handler.py python-services/tests/test_call_end_rtp.py python-services/tests/test_leg_qos_handler.py
git commit -m "feat(engine): record per-leg Media Experience Score on the CDR"
```

---

### Task 4: Deploy the capture and confirm MES populates

**Files:** none — deploys Tasks 1-3 so data accumulates while the report is built.

**Interfaces:**
- Consumes: Tasks 1-3.
- Produces: `rtp_*_rx_mes` populated on live calls, which Task 7 verifies the page against.

- [ ] **Step 1: Run the migration**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') scp -o StrictHostKeyChecking=no \
  database/migrations/2026_07_30_100000_add_rtp_mes_to_call_records_table.php \
  app/Models/CallRecord.php root@123.136.31.91:/tmp/

sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
set -e
cd /var/www/rswitch
php8.3 -l /tmp/2026_07_30_100000_add_rtp_mes_to_call_records_table.php
cp /tmp/2026_07_30_100000_add_rtp_mes_to_call_records_table.php database/migrations/
cp -a app/Models/CallRecord.php app/Models/CallRecord.php.bak.mes
cp /tmp/CallRecord.php app/Models/
chown www-data:www-data database/migrations/2026_07_30_100000_add_rtp_mes_to_call_records_table.php app/Models/CallRecord.php
time sudo -u www-data php8.3 artisan migrate --force'
```

Expected: migration runs. The comparable 12-column migration took 44s across 95 partitions with zero call impact.

- [ ] **Step 2: Deploy the engine files and restart**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') scp -o StrictHostKeyChecking=no \
  python-services/call_control/rtp_qos.py \
  python-services/call_control/call_end_handler.py \
  python-services/call_control/leg_qos_handler.py \
  root@123.136.31.91:/tmp/

sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
set -e
cd /var/www/rswitch/python-services
for f in rtp_qos.py call_end_handler.py leg_qos_handler.py; do
  venv/bin/python -m py_compile /tmp/$f
  cp -a call_control/$f call_control/$f.bak.mes
  cp /tmp/$f call_control/$f
  chown www-data:www-data call_control/$f && chmod 644 call_control/$f
  cp /tmp/$f /opt/rswitch-src/python-services/call_control/$f
done
supervisorctl restart rswitch-api
sleep 8
supervisorctl status rswitch-api'
```

No dialplan change and no `dialplan reload` — the summary already carries `rxmes`.

- [ ] **Step 3: Confirm MES populates**

Wait ~2 minutes, then:

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -e \"
   SELECT COUNT(*) answered,
          SUM(rtp_trunk_rx_mes IS NOT NULL) trunk_mes,
          SUM(rtp_cust_rx_mes IS NOT NULL) cust_mes,
          ROUND(AVG(rtp_trunk_rx_mes),2) avg_trunk_mes,
          ROUND(AVG(rtp_cust_rx_mes),2) avg_cust_mes
   FROM call_records WHERE call_start >= NOW() - INTERVAL 5 MINUTE AND billsec > 0;\""
```

Expected: `trunk_mes` and `cust_mes` close to `answered`; averages plausibly in the 70-90 band (a live sample showed 83.19).

- [ ] **Step 4: Confirm billing untouched and no handler errors**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "mysql -u rswitch -p'9MpT7Jsqrev5orDHp4Medz5c' rswitch -e \"
   SELECT status, COUNT(*) c, ROUND(SUM(total_cost),4) charged FROM call_records
   WHERE call_start >= NOW() - INTERVAL 10 MINUTE GROUP BY status;\"
   grep -c 'LegQos handler error' /var/log/rswitch-python-api.err.log"
```

Expected: a normal charged/unbillable/in_progress split with non-zero `charged`, and `0` LegQos errors.

---

### Task 5: Controller — the quality aggregates

**Files:**
- Modify: `app/Http/Controllers/Admin/OperationalReportController.php`
- Modify: `routes/web.php` (after the `operational-reports/outbound/export` route, ~line 156)
- Test: `tests/Feature/Admin/QualityReportTest.php`

**Interfaces:**
- Consumes: all 14 `rtp_*` columns.
- Produces: route name `admin.operational-reports.quality`; view data keys `stats`, `hourly`, `breakdown`, `groupBy`, `dateFrom`, `dateTo`, `coverage`. Task 6's Blade consumes exactly these.

- [ ] **Step 1: Add the route**

In `routes/web.php`, after the outbound export route:

```php
    Route::get('operational-reports/quality', [Admin\OperationalReportController::class, 'qualityReport'])->name('operational-reports.quality');
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/Admin/QualityReportTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use PHPUnit\Framework\TestCase;

/**
 * Source-level guards on the quality report.
 *
 * There is no local database, so these assert on the controller source the same
 * way AudioHealthFilterTest does. They defend three properties that are silent
 * when broken: partition pruning, tenant isolation, and not surfacing a column
 * known to be wrong.
 */
class QualityReportTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(__DIR__ . '/../../../app/Http/Controllers/Admin/OperationalReportController.php');
    }

    private function method(): string
    {
        $src = $this->source();
        $start = strpos($src, 'public function qualityReport');
        $this->assertNotFalse($start, 'qualityReport() not found');
        $end = strpos($src, "\n    public function ", $start + 10);
        return substr($src, $start, $end === false ? null : $end - $start);
    }

    public function test_every_query_is_bounded_by_call_start(): void
    {
        $m = $this->method();
        // call_records is partitioned by call_start; an unbounded query scans every partition.
        $this->assertStringContainsString('call_start', $m);
        $this->assertGreaterThanOrEqual(
            substr_count($m, 'CallRecord::'),
            substr_count($m, 'call_start'),
            'every CallRecord query in qualityReport must carry a call_start bound'
        );
    }

    public function test_non_super_admin_is_scoped_to_their_hierarchy(): void
    {
        $m = $this->method();
        $this->assertStringContainsString('isSuperAdmin', $m);
        $this->assertStringContainsString('descendantIds', $m);
    }

    public function test_range_is_capped(): void
    {
        $this->assertStringContainsString('31', $this->method(), 'the 31-day cap must be present');
    }

    public function test_unreliable_column_is_not_surfaced(): void
    {
        $this->assertStringNotContainsString('rtp_cust_tx_count', $this->method());
    }
}
```

- [ ] **Step 3: Run to verify it fails**

Run: `/opt/homebrew/bin/php vendor/bin/phpunit tests/Feature/Admin/QualityReportTest.php`
Expected: FAIL — `qualityReport() not found`.

- [ ] **Step 4: Write the controller method**

Add to `app/Http/Controllers/Admin/OperationalReportController.php`, after `outboundCalls()`:

```php
    /**
     * Call Quality — per-leg RTP analysis.
     *
     * Reads call_records directly rather than cdr_summary_hourly, which has no
     * QoS columns. Every query is bounded by call_start so the daily partitions
     * prune, and the range is capped at 31 days for the same reason.
     *
     * rtp_cust_tx_count is deliberately absent: it is known-unreliable (19,083
     * packets on a 7-second call) and this page is where someone would most
     * likely trust it.
     */
    public function qualityReport(Request $request)
    {
        $authUser = auth()->user();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }
        if ($dateFrom->diffInDays($dateTo) > 31) {
            $dateTo = $dateFrom->copy()->addDays(31)->endOfDay();
        }

        $scope = function ($query) use ($authUser, $dateFrom, $dateTo) {
            $query->where('call_flow', 'sip_to_trunk')
                  ->whereBetween('call_start', [$dateFrom, $dateTo]);
            if (! $authUser->isSuperAdmin()) {
                $query->whereIn('user_id', $authUser->descendantIds());
            }
            return $query;
        };

        // Summary. AVG() ignores NULLs by definition, so uncaptured rows never
        // drag an average toward zero -- but the caller still needs to know how
        // thin the sample is, hence the captured/answered coverage pair.
        $stats = $scope(CallRecord::query())->selectRaw('
            COUNT(*) as total_calls,
            SUM(billsec > 0) as answered,
            SUM(rtp_trunk_rx_count IS NOT NULL) as captured,
            AVG(rtp_trunk_rx_mes) as trunk_mes,
            AVG(rtp_cust_rx_mes) as cust_mes,
            AVG(rtp_trunk_rx_jitter) * 1000 as trunk_jitter_ms,
            AVG(rtp_cust_rx_jitter) * 1000 as cust_jitter_ms,
            AVG(rtp_trunk_rtt) * 1000 as trunk_rtt_ms,
            100 * SUM(rtp_trunk_rx_loss) / NULLIF(SUM(rtp_trunk_rx_count), 0) as trunk_loss_pct,
            100 * SUM(rtp_cust_rx_loss) / NULLIF(SUM(rtp_cust_rx_count), 0) as cust_loss_pct,
            SUM(billsec > 0 AND rtp_trunk_rx_count = 0) as no_audio,
            SUM(billsec > 0 AND rtp_trunk_rx_count > 0 AND rtp_trunk_tx_count = 0) as one_way
        ')->first();

        $coverage = $stats->answered > 0
            ? round(100 * $stats->captured / $stats->answered, 1)
            : 0.0;

        // Hourly trend — carrier vs customer jitter.
        $hourly = $scope(CallRecord::query())
            ->whereNotNull('rtp_trunk_rx_jitter')
            ->selectRaw("
                DATE_FORMAT(call_start, '%Y-%m-%d %H:00') as bucket,
                AVG(rtp_trunk_rx_jitter) * 1000 as trunk_jitter_ms,
                AVG(rtp_cust_rx_jitter) * 1000 as cust_jitter_ms,
                COUNT(*) as calls
            ")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        // Breakdown, pivotable. Worst carrier MES first; NULLs last.
        $groupBy = in_array($request->group_by, ['client', 'trunk', 'prefix'], true)
            ? $request->group_by
            : 'client';

        $breakdown = $scope(CallRecord::query())->whereNotNull('rtp_trunk_rx_count');

        $breakdown = match ($groupBy) {
            'trunk' => $breakdown
                ->leftJoin('trunks', 'trunks.id', '=', 'call_records.outgoing_trunk_id')
                ->selectRaw('COALESCE(trunks.name, "(none)") as label'),
            'prefix' => $breakdown
                ->selectRaw('LEFT(call_records.callee, 3) as label'),
            default => $breakdown
                ->leftJoin('users', 'users.id', '=', 'call_records.user_id')
                ->selectRaw('COALESCE(users.name, "(unknown)") as label'),
        };

        $breakdown = $breakdown->selectRaw('
                COUNT(*) as calls,
                SUM(call_records.billsec > 0) as answered,
                AVG(call_records.rtp_trunk_rx_mes) as trunk_mes,
                AVG(call_records.rtp_trunk_rx_jitter) * 1000 as trunk_jitter_ms,
                AVG(call_records.rtp_trunk_rtt) * 1000 as trunk_rtt_ms,
                100 * SUM(call_records.rtp_trunk_rx_loss) / NULLIF(SUM(call_records.rtp_trunk_rx_count), 0) as trunk_loss_pct,
                SUM(call_records.billsec > 0 AND call_records.rtp_trunk_rx_count = 0) as no_audio
            ')
            ->groupBy('label')
            ->orderByRaw('AVG(call_records.rtp_trunk_rx_mes) IS NULL, AVG(call_records.rtp_trunk_rx_mes) ASC')
            ->limit(50)
            ->get();

        return view('admin.operational-reports.quality', compact(
            'stats', 'coverage', 'hourly', 'breakdown', 'groupBy', 'dateFrom', 'dateTo'
        ));
    }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `/opt/homebrew/bin/php vendor/bin/phpunit tests/Feature/Admin/QualityReportTest.php`
Expected: `OK (4 tests)`

- [ ] **Step 6: Verify the guards actually catch regressions**

Temporarily delete the `->whereIn('user_id', $authUser->descendantIds());` line, re-run, confirm `test_non_super_admin_is_scoped_to_their_hierarchy` FAILS, then `git checkout app/Http/Controllers/Admin/OperationalReportController.php` and confirm it passes again. Record both observations.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/OperationalReportController.php routes/web.php tests/Feature/Admin/QualityReportTest.php
git commit -m "feat(reports): add the call quality aggregate endpoint"
```

---

### Task 6: The Call Quality page

**Files:**
- Create: `resources/views/admin/operational-reports/quality.blade.php`
- Modify: `resources/views/layouts/admin.blade.php` (nav, after the Outbound Calls entry)

**Interfaces:**
- Consumes: view data `stats`, `coverage`, `hourly`, `breakdown`, `groupBy`, `dateFrom`, `dateTo` from Task 5; route `admin.operational-reports.quality`.
- Produces: nothing later tasks depend on.

- [ ] **Step 1: Add the nav entry**

In `resources/views/layouts/admin.blade.php`, immediately after the Outbound Calls line:

```blade
                        <a href="{{ route('admin.operational-reports.quality') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.quality') ? 'active' : 'text-gray-500' }}">Call Quality</a>
```

- [ ] **Step 2: Create the view**

```blade
<x-admin-layout>
    <x-slot name="header">Call Quality</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Call Quality</h2>
            <p class="page-subtitle">Per-leg RTP analysis — carrier vs customer</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row flex-wrap">
            <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}" class="filter-input" style="width:auto;">
            <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}" class="filter-input" style="width:auto;">
            <select name="group_by" class="filter-input" style="width:auto;">
                <option value="client" {{ $groupBy === 'client' ? 'selected' : '' }}>By Client</option>
                <option value="trunk" {{ $groupBy === 'trunk' ? 'selected' : '' }}>By Trunk</option>
                <option value="prefix" {{ $groupBy === 'prefix' ? 'selected' : '' }}>By Destination</option>
            </select>
            <button type="submit" class="btn-search-admin">Apply</button>
        </form>
    </div>

    {{-- Coverage: an average over a thin sample must never read as complete --}}
    <div class="mb-3 px-4 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
        QoS captured on <strong>{{ number_format($stats->captured) }}</strong> of
        <strong>{{ number_format($stats->answered) }}</strong> answered calls
        (<strong>{{ $coverage }}%</strong>). Averages below cover only captured calls.
        Customer-side transmit counts are not shown — that counter is known to be unreliable.
    </div>

    {{-- Summary cards --}}
    @php
        $band = function ($mes) {
            if ($mes === null) return ['—', 'text-gray-400'];
            if ($mes >= 80) return ['Excellent', 'text-emerald-600'];
            if ($mes >= 60) return ['Good', 'text-blue-600'];
            if ($mes >= 40) return ['Fair', 'text-amber-600'];
            return ['Poor', 'text-red-600'];
        };
        [$trunkBand, $trunkColor] = $band($stats->trunk_mes);
        [$custBand, $custColor] = $band($stats->cust_mes);
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Carrier MES</p>
            <p class="text-lg font-semibold {{ $trunkColor }}">{{ $stats->trunk_mes !== null ? number_format($stats->trunk_mes, 1) : '—' }}
                <span class="text-xs font-normal">{{ $trunkBand }}</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Customer MES</p>
            <p class="text-lg font-semibold {{ $custColor }}">{{ $stats->cust_mes !== null ? number_format($stats->cust_mes, 1) : '—' }}
                <span class="text-xs font-normal">{{ $custBand }}</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Carrier jitter</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->trunk_jitter_ms !== null ? number_format($stats->trunk_jitter_ms, 1) : '—' }} ms</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Customer jitter</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->cust_jitter_ms !== null ? number_format($stats->cust_jitter_ms, 1) : '—' }} ms</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Carrier loss</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->trunk_loss_pct !== null ? number_format($stats->trunk_loss_pct, 3) : '—' }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Customer loss</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->cust_loss_pct !== null ? number_format($stats->cust_loss_pct, 3) : '—' }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Avg RTT</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->trunk_rtt_ms !== null ? number_format($stats->trunk_rtt_ms, 1) : '—' }} ms</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">No audio / one-way</p>
            <p class="text-lg font-semibold {{ ($stats->no_audio + $stats->one_way) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                {{ number_format($stats->no_audio) }} / {{ number_format($stats->one_way) }}</p>
        </div>
    </div>

    {{-- Trend --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <p class="text-sm font-semibold text-gray-700 mb-2">Jitter over time (ms)</p>
        <canvas id="jitterChart" height="80"></canvas>
    </div>

    {{-- Breakdown --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ ucfirst($groupBy) }}</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Calls</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Answered</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">MES</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Jitter</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Loss</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">RTT</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">No audio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdown as $row)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} border-b border-gray-100">
                        <td class="px-3 py-2 text-gray-900">{{ $row->label }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ number_format($row->calls) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ number_format($row->answered) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-medium {{ $row->trunk_mes !== null && $row->trunk_mes < 60 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $row->trunk_mes !== null ? number_format($row->trunk_mes, 1) : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ $row->trunk_jitter_ms !== null ? number_format($row->trunk_jitter_ms, 1) : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ $row->trunk_loss_pct !== null ? number_format($row->trunk_loss_pct, 2) . '%' : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ $row->trunk_rtt_ms !== null ? number_format($row->trunk_rtt_ms, 0) : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums {{ $row->no_audio > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">{{ number_format($row->no_audio) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-8 text-center text-gray-400 text-sm">No QoS data captured in this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    {{-- SRI hash verified against the real CDN payload (Chart.js v4.4.1, 205,399 bytes,
         hash stable across two independent fetches). Without it a CDN compromise runs
         arbitrary JS in an authenticated admin session. The two existing Chart.js tags
         in this codebase lack SRI -- worth retrofitting separately, out of scope here. --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
            integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4"
            crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('jitterChart');
            if (!ctx) return;
            var rows = @json($hourly);
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: rows.map(function (r) { return r.bucket.slice(11, 16); }),
                    datasets: [
                        { label: 'Carrier', data: rows.map(function (r) { return r.trunk_jitter_ms; }),
                          borderColor: '#ef4444', backgroundColor: 'transparent', tension: 0.3 },
                        { label: 'Customer', data: rows.map(function (r) { return r.cust_jitter_ms; }),
                          borderColor: '#6366f1', backgroundColor: 'transparent', tension: 0.3 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'ms' } } }
                }
            });
        });
    </script>
    @endpush
</x-admin-layout>
```

- [ ] **Step 3: Confirm the layout supports `@push('scripts')`**

Run: `grep -n "@stack('scripts')" resources/views/layouts/admin.blade.php`
Expected: a match. If there is no `@stack('scripts')`, drop the `@push`/`@endpush` wrapper and place the two `<script>` blocks inline at the end of the view instead — do not add a stack to the layout.

- [ ] **Step 4: Commit**

```bash
git add resources/views/admin/operational-reports/quality.blade.php resources/views/layouts/admin.blade.php
git commit -m "feat(reports): add the Call Quality page"
```

---

### Task 7: Deploy the page and verify

**Files:** none — deploys Tasks 5-6.

- [ ] **Step 1: Deploy**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') scp -o StrictHostKeyChecking=no \
  app/Http/Controllers/Admin/OperationalReportController.php \
  routes/web.php \
  resources/views/admin/operational-reports/quality.blade.php \
  resources/views/layouts/admin.blade.php \
  root@123.136.31.91:/tmp/

sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
set -e
cd /var/www/rswitch
php8.3 -l /tmp/OperationalReportController.php
php8.3 -l /tmp/web.php
php8.3 -l /tmp/quality.blade.php || true   # Blade is not plain PHP; view:cache below is the real check
cp -a app/Http/Controllers/Admin/OperationalReportController.php app/Http/Controllers/Admin/OperationalReportController.php.bak.quality
cp -a routes/web.php routes/web.php.bak.quality
cp -a resources/views/layouts/admin.blade.php resources/views/layouts/admin.blade.php.bak.quality
cp /tmp/OperationalReportController.php app/Http/Controllers/Admin/
cp /tmp/web.php routes/
cp /tmp/quality.blade.php resources/views/admin/operational-reports/
cp /tmp/admin.blade.php resources/views/layouts/
chown -R www-data:www-data app/Http/Controllers/Admin routes resources/views
sudo -u www-data php8.3 artisan route:clear && sudo -u www-data php8.3 artisan view:clear
sudo -u www-data php8.3 artisan view:cache 2>&1 | tail -2'
```

Expected: `Blade templates cached successfully.`

- [ ] **Step 2: Verify the route exists**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "cd /var/www/rswitch && sudo -u www-data php8.3 artisan route:list --name=quality"
```

Expected: one row for `admin.operational-reports.quality`.

- [ ] **Step 3: Render the page as a super admin**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
cd /var/www/rswitch && sudo -u www-data php8.3 artisan tinker --execute="
\$u = App\Models\User::where(\"role\",\"super_admin\")->first();
auth()->login(\$u);
\$req = Illuminate\Http\Request::create(\"/admin/operational-reports/quality\",\"GET\",[]);
app()->instance(\"request\", \$req);
\$c = new App\Http\Controllers\Admin\OperationalReportController();
\$d = \$c->qualityReport(\$req)->getData();
echo \"coverage: \".\$d[\"coverage\"].\"%\".PHP_EOL;
echo \"trunk MES: \".var_export(\$d[\"stats\"]->trunk_mes, true).PHP_EOL;
echo \"hourly buckets: \".\$d[\"hourly\"]->count().PHP_EOL;
echo \"breakdown rows: \".\$d[\"breakdown\"]->count().PHP_EOL;
"'
```

Expected: a non-zero coverage percentage, a plausible MES, at least one hourly bucket, and breakdown rows.

- [ ] **Step 4: Verify tenant isolation**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 '
cd /var/www/rswitch && sudo -u www-data php8.3 artisan tinker --execute="
\$r = App\Models\User::where(\"role\",\"reseller\")->where(\"id\",47)->first();
auth()->login(\$r);
\$req = Illuminate\Http\Request::create(\"/admin/operational-reports/quality\",\"GET\",[]);
app()->instance(\"request\", \$req);
\$c = new App\Http\Controllers\Admin\OperationalReportController();
\$d = \$c->qualityReport(\$req)->getData();
echo \"reseller 47 sees \".\$d[\"stats\"]->total_calls.\" calls, \".\$d[\"breakdown\"]->count().\" breakdown rows\".PHP_EOL;
foreach (\$d[\"breakdown\"]->take(5) as \$row) { echo \"  \".\$row->label.PHP_EOL; }
"'
```

Expected: strictly fewer calls than the super-admin figure, and every breakdown label belonging to reseller 47's own clients. **If a CyberNest client appears here, tenant isolation is broken — stop and fix before proceeding.**

- [ ] **Step 5: Confirm nothing else regressed**

```bash
sshpass -f <(printf '%s' 'rgl@2020#') ssh -o StrictHostKeyChecking=no root@123.136.31.91 \
  "tail -c 4000 /var/www/rswitch/storage/logs/laravel.log | grep -iE 'ERROR|Exception' | tail -3; echo '(none above = clean)'"
```

---

## Rollback

- **Tasks 5-7** — restore the `.bak.quality` copies, `route:clear`, `view:clear`. The page is read-only; removing it cannot affect calls or billing.
- **Tasks 2-4** — restore the `.bak.mes` engine copies and `supervisorctl restart rswitch-api` (~1-2s). Capture reverts to the 12 columns.
- **Task 1** — `php artisan migrate:rollback --step=1`. Additive columns; dropping them loses only MES history.

Stopping after Task 4 leaves MES accumulating with no page — harmless, and the data is queryable by SQL.
