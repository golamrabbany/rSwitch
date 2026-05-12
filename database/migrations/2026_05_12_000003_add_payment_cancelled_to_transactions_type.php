<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Split the previously-unified 'payment_failed' into cancelled vs failed
        // because they describe different intents:
        //   payment_cancelled — user actively chose to abandon
        //   payment_failed    — gateway or validation error
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

        // Re-classify existing rows whose description signals a cancellation.
        DB::statement("
            UPDATE transactions
            SET type = 'payment_cancelled'
            WHERE type = 'payment_failed'
              AND (description LIKE '%cancel%' OR description LIKE '%CANCEL%')
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE transactions
            SET type = 'payment_failed'
            WHERE type = 'payment_cancelled'
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
                'payment_failed'
            ) NOT NULL
        ");
    }
};
