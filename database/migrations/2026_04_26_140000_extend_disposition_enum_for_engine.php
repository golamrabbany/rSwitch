<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE call_records MODIFY COLUMN disposition
            ENUM('ANSWERED','NO ANSWER','BUSY','FAILED','CANCEL','UNREACHABLE','NO_ROUTE')
            DEFAULT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE call_records SET disposition = 'FAILED'
            WHERE disposition IN ('UNREACHABLE','NO_ROUTE')
        ");
        DB::statement("
            ALTER TABLE call_records MODIFY COLUMN disposition
            ENUM('ANSWERED','NO ANSWER','BUSY','FAILED','CANCEL')
            DEFAULT NULL
        ");
    }
};
