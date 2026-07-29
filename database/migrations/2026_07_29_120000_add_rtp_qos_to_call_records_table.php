<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-leg RTP quality, captured at hangup from CHANNEL(rtcp,all,audio). NOT
     * CHANNEL(rtpqos,...): that returns empty inside a hangup handler because the
     * RTP instance is already torn down by the time hangup handlers run; rtcp
     * survives. See python-services/call_control/rtp_qos.py for the parsing.
     *
     * All columns are nullable on purpose: NULL means "not captured" (rows from
     * before this migration, or a leg that never came up), while 0 means "no
     * packets arrived". That distinction is the no-audio signal itself, so these
     * must never be given a 0 default.
     *
     * call_records is partitioned by call_start; ADD COLUMN is safe here (the
     * same operation was used for origin_sip_account_id).
     */
    public function up(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            // Customer leg: Asterisk <-> the client's phone
            $table->unsignedInteger('rtp_cust_rx_count')->nullable()->after('trunk_cost');
            // KNOWN UNRELIABLE (live sample, 2026-07-29): averaged 213.5 packets/sec
            // against an expected ~50, with 4 of 26 rows implausible (e.g. 19,083
            // packets on a 7-second call). Suspected cause: on devices running many
            // concurrent channels, the RTCP tx counter aggregates across channels
            // instead of reporting this one. All eleven other columns measured clean.
            // Kept (not dropped) because the raw data is still wanted -- just don't
            // trust it standalone. See the one_way filter below for the mitigation.
            $table->unsignedInteger('rtp_cust_tx_count')->nullable()->after('rtp_cust_rx_count');
            $table->unsignedInteger('rtp_cust_rx_loss')->nullable()->after('rtp_cust_tx_count');
            $table->unsignedInteger('rtp_cust_tx_loss')->nullable()->after('rtp_cust_rx_loss');
            $table->decimal('rtp_cust_rx_jitter', 8, 3)->nullable()->after('rtp_cust_tx_loss');
            $table->decimal('rtp_cust_rtt', 8, 3)->nullable()->after('rtp_cust_rx_jitter');

            // Carrier leg: Asterisk <-> the trunk
            $table->unsignedInteger('rtp_trunk_rx_count')->nullable()->after('rtp_cust_rtt');
            $table->unsignedInteger('rtp_trunk_tx_count')->nullable()->after('rtp_trunk_rx_count');
            $table->unsignedInteger('rtp_trunk_rx_loss')->nullable()->after('rtp_trunk_tx_count');
            $table->unsignedInteger('rtp_trunk_tx_loss')->nullable()->after('rtp_trunk_rx_loss');
            $table->decimal('rtp_trunk_rx_jitter', 8, 3)->nullable()->after('rtp_trunk_tx_loss');
            $table->decimal('rtp_trunk_rtt', 8, 3)->nullable()->after('rtp_trunk_rx_jitter');
        });
    }

    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->dropColumn([
                'rtp_cust_rx_count', 'rtp_cust_tx_count', 'rtp_cust_rx_loss',
                'rtp_cust_tx_loss', 'rtp_cust_rx_jitter', 'rtp_cust_rtt',
                'rtp_trunk_rx_count', 'rtp_trunk_tx_count', 'rtp_trunk_rx_loss',
                'rtp_trunk_tx_loss', 'rtp_trunk_rx_jitter', 'rtp_trunk_rtt',
            ]);
        });
    }
};
