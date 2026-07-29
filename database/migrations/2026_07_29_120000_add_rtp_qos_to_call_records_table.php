<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-leg RTP quality, captured at hangup from CHANNEL(rtpqos,audio,...).
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
