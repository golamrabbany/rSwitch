<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ps_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('ps_contacts', 'endpoint')) {
                $table->string('endpoint', 40)->nullable()->after('reg_server');
            }
            if (!Schema::hasColumn('ps_contacts', 'via_addr')) {
                $table->string('via_addr', 40)->nullable();
            }
            if (!Schema::hasColumn('ps_contacts', 'via_port')) {
                $table->integer('via_port')->nullable();
            }
            if (!Schema::hasColumn('ps_contacts', 'call_id')) {
                $table->string('call_id', 255)->nullable();
            }
            if (!Schema::hasColumn('ps_contacts', 'prune_on_boot')) {
                $table->string('prune_on_boot', 5)->nullable();
            }
            if (!Schema::hasColumn('ps_contacts', 'authenticate_qualify')) {
                $table->string('authenticate_qualify', 5)->nullable();
            }
            if (!Schema::hasColumn('ps_contacts', 'qualify_timeout')) {
                $table->string('qualify_timeout', 10)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ps_contacts', function (Blueprint $table) {
            $table->dropColumn(['endpoint', 'via_addr', 'via_port', 'call_id', 'prune_on_boot', 'authenticate_qualify', 'qualify_timeout']);
        });
    }
};
