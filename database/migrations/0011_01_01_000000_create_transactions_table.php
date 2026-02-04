<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['topup', 'call_charge', 'did_charge', 'refund', 'adjustment', 'invoice_payment']);
            $table->decimal('amount', 12, 4);
            $table->decimal('balance_after', 12, 4);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'created_at'], 'idx_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
