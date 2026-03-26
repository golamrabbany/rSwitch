<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prevent double-charge on Celery retry: only one call_charge and one
        // reseller_call_charge per call_record per user.
        // Using (user_id, reference_type, reference_id, type) — safe because:
        // - call_charge: user_id=client, reference_type=call_record, reference_id=CDR, type=call_charge → unique per CDR
        // - reseller_call_charge: user_id=reseller, same CDR → unique per CDR (different user_id)
        // - topup: reference_type=manual_admin (not call_record) → unaffected
        // - NULL reference_id: MySQL treats each NULL as unique in unique indexes → safe
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
