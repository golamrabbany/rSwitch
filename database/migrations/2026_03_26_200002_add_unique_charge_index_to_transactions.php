<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up any existing duplicates before adding unique index
        DB::statement("
            DELETE t1 FROM transactions t1
            INNER JOIN transactions t2
            WHERE t1.id > t2.id
            AND t1.user_id = t2.user_id
            AND t1.reference_type = t2.reference_type
            AND t1.reference_id = t2.reference_id
            AND t1.type = t2.type
            AND t1.reference_id IS NOT NULL
        ");

        // Prevent double-charge on Celery retry: only one call_charge and one
        // reseller_call_charge per call_record per user.
        Schema::table('transactions', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'reference_type', 'reference_id', 'type'],
                'idx_unique_charge_per_ref'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('idx_unique_charge_per_ref');
        });
    }
};
