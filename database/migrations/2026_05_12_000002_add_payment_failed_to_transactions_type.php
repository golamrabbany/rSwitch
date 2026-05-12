<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the transactions.type enum with a dedicated value for non-monetary
        // records of failed/cancelled payments. Previously these landed under
        // 'adjustment' which is semantically misleading (adjustments imply real
        // balance changes).
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

        // Re-classify the rows that the failed-attempt logger inserted as
        // 'adjustment' (identifiable by reference_type='payment' AND amount=0).
        DB::statement("
            UPDATE transactions
            SET type = 'payment_failed'
            WHERE type = 'adjustment'
              AND reference_type = 'payment'
              AND amount = 0
        ");
    }

    public function down(): void
    {
        // Re-classify back so we don't lose data when the enum value goes away.
        DB::statement("
            UPDATE transactions
            SET type = 'adjustment'
            WHERE type = 'payment_failed'
        ");

        DB::statement("
            ALTER TABLE transactions
            MODIFY COLUMN type ENUM(
                'topup',
                'call_charge',
                'did_charge',
                'refund',
                'adjustment',
                'invoice_payment'
            ) NOT NULL
        ");
    }
};
