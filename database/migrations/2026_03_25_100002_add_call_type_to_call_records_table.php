<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->enum('call_type', ['regular', 'broadcast'])->default('regular')->after('call_flow');
            $table->unsignedBigInteger('broadcast_id')->nullable()->after('call_type');
            $table->index('call_type', 'idx_call_type');
            $table->index('broadcast_id', 'idx_broadcast_id');
        });
    }

    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table) {
            $table->dropIndex('idx_call_type');
            $table->dropIndex('idx_broadcast_id');
            $table->dropColumn(['call_type', 'broadcast_id']);
        });
    }
};
