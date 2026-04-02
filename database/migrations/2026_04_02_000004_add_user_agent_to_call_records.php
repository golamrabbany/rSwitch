<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            if (!Schema::hasColumn('call_records', 'user_agent')) {
                $table->string('user_agent', 255)->nullable()->after('dst_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
