<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add destination_sip_account_id for internal SIP-to-SIP calls
        DB::statement("
            ALTER TABLE call_records
            ADD COLUMN destination_sip_account_id BIGINT UNSIGNED NULL AFTER did_id
        ");

        // Add index for destination SIP account lookups
        DB::statement("
            ALTER TABLE call_records
            ADD INDEX idx_dest_sip_account (destination_sip_account_id, call_start)
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE call_records
            DROP INDEX idx_dest_sip_account
        ");

        DB::statement("
            ALTER TABLE call_records
            DROP COLUMN destination_sip_account_id
        ");
    }
};
