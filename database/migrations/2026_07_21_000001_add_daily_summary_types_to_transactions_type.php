<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the daily-aggregate transaction types the billing engine already writes.
     *
     * `billing/tasks.py::daily_call_summary` (celery beat, 00:05 daily) replaced
     * per-call transaction rows with one aggregate row per user per day — it is
     * the ONLY thing that produces a customer-visible statement now that
     * charge_call() debits the balance directly.
     *
     * That task shipped writing type='daily_call_charge' / 'daily_reseller_charge',
     * but no migration ever added those values to the enum. Every nightly run since
     * 2026-06-14 failed with "ERROR 1265 Data truncated for column 'type'", silently:
     * balances kept debiting correctly while the audit trail wrote nothing. Result was
     * ~211k charged calls across Jun+Jul with zero statement rows, and customers unable
     * to verify a single billed minute.
     *
     * Deliberately NOT backfilling historic summaries here — the gap is closed going
     * forward only.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE transactions
            MODIFY COLUMN type ENUM(
                'topup',
                'call_charge',
                'did_charge',
                'refund',
                'adjustment',
                'invoice_payment',
                'payment_failed',
                'payment_cancelled',
                'daily_call_charge',
                'daily_reseller_charge'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        // Aggregate rows have no pre-existing type to fold back into; drop them
        // rather than silently mislabelling them as per-call charges.
        DB::statement("
            DELETE FROM transactions
            WHERE type IN ('daily_call_charge', 'daily_reseller_charge')
        ");

        DB::statement("
            ALTER TABLE transactions
            MODIFY COLUMN type ENUM(
                'topup',
                'call_charge',
                'did_charge',
                'refund',
                'adjustment',
                'invoice_payment',
                'payment_failed',
                'payment_cancelled'
            ) NOT NULL
        ");
    }
};
