<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('user_id')->constrained();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('call_charges', 12, 4);
            $table->decimal('did_charges', 12, 4);
            $table->decimal('total_amount', 12, 4);
            $table->decimal('tax_amount', 12, 4)->default(0);
            $table->enum('status', ['draft', 'issued', 'paid', 'overdue', 'cancelled']);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
