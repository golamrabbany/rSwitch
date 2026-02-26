<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trunk_routes', function (Blueprint $table) {
            $table->string('remove_prefix', 20)->nullable()->after('weight');
            $table->string('add_prefix', 20)->nullable()->after('remove_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trunk_routes', function (Blueprint $table) {
            $table->dropColumn(['remove_prefix', 'add_prefix']);
        });
    }
};
