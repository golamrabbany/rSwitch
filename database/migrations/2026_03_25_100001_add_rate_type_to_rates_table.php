<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->enum('rate_type', ['regular', 'broadcast'])->default('regular')->after('status');
            $table->index(['prefix', 'rate_type', 'status'], 'idx_prefix_rate_type');
        });
    }

    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropIndex('idx_prefix_rate_type');
            $table->dropColumn('rate_type');
        });
    }
};
