<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 12, 4);
            $table->string('currency', 3)->default('USD');
            $table->enum('payment_method', [
                'online_stripe', 'online_paypal', 'online_sslcommerz',
                'bank_transfer', 'manual_admin', 'manual_reseller',
            ]);
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->unsignedBigInteger('recharged_by')->nullable();
            $table->string('notes', 500)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamps();

            $table->foreign('recharged_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->index(['user_id', 'created_at'], 'idx_user_date');
            $table->index('status', 'idx_status');
            $table->index('gateway_transaction_id', 'idx_gateway_txn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
