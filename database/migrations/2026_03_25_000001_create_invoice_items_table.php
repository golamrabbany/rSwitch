<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->enum('type', ['call_charges', 'did_charges', 'adjustment'])->default('call_charges');
            $table->string('destination', 50)->nullable();
            $table->string('client_name', 150)->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('minutes', 12, 2)->default(0);
            $table->decimal('rate_per_minute', 10, 6)->default(0);
            $table->decimal('amount', 12, 4)->default(0);
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
