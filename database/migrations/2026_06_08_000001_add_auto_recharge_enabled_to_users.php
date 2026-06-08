<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'auto_recharge_enabled')) {
                $table->boolean('auto_recharge_enabled')->default(false)->after('low_balance_threshold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'auto_recharge_enabled')) {
                $table->dropColumn('auto_recharge_enabled');
            }
        });
    }
};
