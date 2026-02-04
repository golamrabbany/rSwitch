<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_group_id')->constrained()->cascadeOnDelete();
            $table->string('prefix', 20)->index();
            $table->string('destination', 100);
            $table->decimal('rate_per_minute', 10, 6);
            $table->decimal('connection_fee', 10, 6)->default(0);
            $table->unsignedInteger('min_duration')->default(0);
            $table->unsignedInteger('billing_increment')->default(6);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamps();

            $table->index(['rate_group_id', 'prefix', 'effective_date'], 'idx_prefix_date');
            $table->index(['effective_date', 'end_date'], 'idx_effective');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
