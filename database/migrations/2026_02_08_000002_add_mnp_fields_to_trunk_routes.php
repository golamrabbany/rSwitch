<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trunk_routes', function (Blueprint $table) {
            $table->boolean('mnp_enabled')->default(false)->after('weight');
            $table->string('mnp_prefix', 10)->nullable()->after('mnp_enabled');
            $table->unsignedTinyInteger('mnp_insert_position')->default(3)->after('mnp_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('trunk_routes', function (Blueprint $table) {
            $table->dropColumn(['mnp_enabled', 'mnp_prefix', 'mnp_insert_position']);
        });
    }
};
