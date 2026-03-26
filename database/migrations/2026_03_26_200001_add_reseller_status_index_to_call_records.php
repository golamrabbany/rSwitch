<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for reseller auto-cutoff: finding active calls per reseller.
        // Query: WHERE reseller_id = ? AND status = 'in_progress'
        // Includes call_start for partition pruning on the partitioned call_records table.
        DB::statement('
            ALTER TABLE call_records
            ADD KEY idx_reseller_status (reseller_id, status, call_start)
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE call_records
            DROP KEY idx_reseller_status
        ');
    }
};
