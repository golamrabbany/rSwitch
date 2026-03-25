<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sip_range_start', 20)->nullable()->after('max_channels');
            $table->string('sip_range_end', 20)->nullable()->after('sip_range_start');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sip_range_start', 'sip_range_end']);
        });
    }
};
