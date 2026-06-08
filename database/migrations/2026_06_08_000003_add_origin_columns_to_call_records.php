<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * call_records: preserve the ACTUAL originating SIP account + caller when a
 * Random-CLI call is billed/attributed to a different pool member (B). The
 * outbound engine writes these on every sip_to_trunk CDR — without them a
 * fresh install's route_outbound INSERT fails on unknown columns.
 *
 * call_records is monthly-partitioned; ADD COLUMN applies across all partitions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            if (! Schema::hasColumn('call_records', 'origin_sip_account_id')) {
                $table->unsignedBigInteger('origin_sip_account_id')->nullable()->after('sip_account_id');
            }
            if (! Schema::hasColumn('call_records', 'origin_caller')) {
                $table->string('origin_caller', 40)->nullable()->after('caller');
            }
        });
    }

    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            foreach (['origin_sip_account_id', 'origin_caller'] as $col) {
                if (Schema::hasColumn('call_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
