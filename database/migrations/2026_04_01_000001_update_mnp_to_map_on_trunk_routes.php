<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trunk_routes', function (Blueprint $table) {
            // Drop all old MNP fields — only keep mnp_enabled boolean
            if (Schema::hasColumn('trunk_routes', 'mnp_prefix')) {
                $table->dropColumn('mnp_prefix');
            }
            if (Schema::hasColumn('trunk_routes', 'mnp_insert_position')) {
                $table->dropColumn('mnp_insert_position');
            }
            if (Schema::hasColumn('trunk_routes', 'mnp_map')) {
                $table->dropColumn('mnp_map');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trunk_routes', function (Blueprint $table) {
            $table->string('mnp_prefix', 10)->nullable()->after('mnp_enabled');
            $table->unsignedTinyInteger('mnp_insert_position')->default(3)->after('mnp_prefix');
        });
    }
};
