<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PJSIP realtime: add from_domain / from_user to ps_endpoints.
 *
 * The trimmed realtime schema omitted these standard PJSIP columns, so the
 * From URI host fell back to whatever local IP Asterisk guessed — on a
 * dual-homed/dual-default-route box that leaked the PUBLIC IP to the carrier.
 * Setting a trunk's from_domain (e.g. the private carrier-facing source IP)
 * fixes the advertised identity. Sorcery realtime picks these up by column name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ps_endpoints', function (Blueprint $table) {
            if (! Schema::hasColumn('ps_endpoints', 'from_domain')) {
                $table->string('from_domain', 80)->nullable()->after('callerid');
            }
            if (! Schema::hasColumn('ps_endpoints', 'from_user')) {
                $table->string('from_user', 80)->nullable()->after('callerid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ps_endpoints', function (Blueprint $table) {
            foreach (['from_domain', 'from_user'] as $col) {
                if (Schema::hasColumn('ps_endpoints', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
