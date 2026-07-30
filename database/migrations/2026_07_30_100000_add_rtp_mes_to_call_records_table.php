<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Media Experience Score per leg, from CHANNEL(rtcp,all,audio).
     *
     * Asterisk already returns rxmes/txmes in the same summary the engine reads
     * for every call -- they were simply discarded. Only the rx score is kept per
     * leg: it measures the audio ARRIVING from the customer phone and from the
     * carrier, which is what a quality investigation asks about. txmes is our own
     * send-side score and answers nothing this report asks.
     *
     * Scale is 0-100, NOT the 1-5 MOS scale. decimal(5,2) fits 0.00-999.99.
     *
     * Nullable with no default, like the twelve RTP columns before them: NULL
     * means "not captured", 0 means "measured and terrible".
     */
    public function up(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->decimal('rtp_cust_rx_mes', 5, 2)->nullable()->after('rtp_cust_rtt');
            $table->decimal('rtp_trunk_rx_mes', 5, 2)->nullable()->after('rtp_trunk_rtt');
        });
    }

    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->dropColumn(['rtp_cust_rx_mes', 'rtp_trunk_rx_mes']);
        });
    }
};
