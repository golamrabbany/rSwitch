<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the indexes the CDR screens actually filter on.
     *
     * Every pre-existing index on call_records carries call_start as its SECOND
     * column (PRIMARY(id, call_start), idx_uuid, idx_user_status(user_id, status,
     * call_start), idx_caller, idx_callee). Nothing served a filter on status,
     * call_flow, or call_start alone — so the common CDR queries fell back to a
     * full scan and MySQL could not satisfy "ORDER BY call_start DESC" from an
     * index either ("Using filesort").
     *
     * That stayed cheap only while daily partition pruning kept each scan small.
     * Once partition maintenance stopped on 2026-06-11 and everything piled into
     * p_future (812k rows), each scan became the whole backlog. Measured on
     * pacevoice 2026-07-21 with 839k rows:
     *
     *     status + ORDER BY call_start   4,421ms -> 14ms
     *     call_flow + call_start range   4,189ms -> 15ms
     *     bare call_start range          4,415ms -> 12ms
     *     rate_batch orphan sweep        1,048ms -> 12ms
     *     daily_call_summary aggregate  ~3,350ms -> 39ms
     *
     * These indexes make the queries fast independently of partition health,
     * which is the point: partitioning is a storage-lifecycle mechanism and
     * should not be the only thing standing between the UI and a full scan.
     *
     * Applied with ALGORITHM=INPLACE, LOCK=NONE — online, no write blocking
     * (verified: CDR inserts continued throughout, ~7s for the largest index).
     */
    public function up(): void
    {
        foreach (self::indexes() as $name => $columns) {
            if (self::indexExists($name)) {
                continue;
            }

            DB::statement(
                "ALTER TABLE call_records ADD INDEX {$name} ({$columns}), "
                . "ALGORITHM=INPLACE, LOCK=NONE"
            );
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::indexes()) as $name) {
            if (self::indexExists($name)) {
                DB::statement("ALTER TABLE call_records DROP INDEX {$name}");
            }
        }
    }

    private static function indexes(): array
    {
        return [
            // CDR lists filtered by billing status, newest first
            'idx_status_start' => 'status, call_start',
            // CDR lists filtered by direction/flow within a date range
            'idx_flow_start'   => 'call_flow, call_start',
            // Date-range reports with no other filter; also gives MySQL a
            // leading-call_start index, which no existing index provided
            'idx_call_start'   => 'call_start',
        ];
    }

    private static function indexExists(string $name): bool
    {
        return DB::selectOne(
            "SELECT 1 AS found FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'call_records'
               AND index_name = ?
             LIMIT 1",
            [$name]
        ) !== null;
    }
};
