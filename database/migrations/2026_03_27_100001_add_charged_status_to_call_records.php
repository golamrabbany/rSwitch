<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'charged' to the status enum for daily aggregation billing.
        // CDR flow: in_progress → rated → charged (balance deducted, no per-call transaction)
        DB::statement("
            ALTER TABLE call_records
            MODIFY COLUMN status ENUM('in_progress','rated','charged','failed','unbillable','completed')
            NOT NULL DEFAULT 'in_progress'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE call_records
            MODIFY COLUMN status ENUM('in_progress','rated','failed','unbillable','completed')
            NOT NULL DEFAULT 'in_progress'
        ");
    }
};
