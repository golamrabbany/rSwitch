<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('transfer_type', ['sip_account', 'client']);
            $table->unsignedBigInteger('transferred_item_id');
            $table->string('transferred_item_type', 30);
            $table->unsignedBigInteger('from_parent_id');
            $table->unsignedBigInteger('to_parent_id');
            $table->foreignId('performed_by')->constrained('users');
            $table->string('reason', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['transferred_item_type', 'transferred_item_id'], 'idx_item');
            $table->index('created_at', 'idx_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_logs');
    }
};
