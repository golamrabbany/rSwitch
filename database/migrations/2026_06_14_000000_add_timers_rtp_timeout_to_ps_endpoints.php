<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PJSIP realtime: add session-timer + RTP-timeout columns to ps_endpoints.
 *
 * The trimmed realtime schema omitted these, so Asterisk fell back to its
 * defaults (timers=yes, timers_sess_expires=1800) — which refreshes the call
 * session at the 1800/2 = 900s mark. Carriers that don't honor the mid-call
 * re-INVITE then tear long calls down at ~15 minutes. Adding `timers` lets a
 * trunk be set to `no` (stop the 900s refresh) and `rtp_timeout` restores the
 * dead-call detection that session timers otherwise provided. Sorcery realtime
 * picks these up by column name; NULL = Asterisk default (unchanged behaviour).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ps_endpoints', function (Blueprint $table) {
            if (! Schema::hasColumn('ps_endpoints', 'timers')) {
                $table->enum('timers', ['forced', 'no', 'required', 'yes', 'accept'])->nullable();
            }
            if (! Schema::hasColumn('ps_endpoints', 'rtp_timeout')) {
                $table->integer('rtp_timeout')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ps_endpoints', function (Blueprint $table) {
            foreach (['timers', 'rtp_timeout'] as $col) {
                if (Schema::hasColumn('ps_endpoints', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
