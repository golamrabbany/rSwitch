<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trunk_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trunk_id')->constrained()->cascadeOnDelete();
            $table->string('prefix', 20);
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('days_of_week', 20)->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->unsignedInteger('priority')->default(1);
            $table->unsignedInteger('weight')->default(100);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamps();

            $table->index(['prefix', 'priority'], 'idx_prefix_priority');
            $table->index(['prefix', 'time_start', 'time_end'], 'idx_prefix_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trunk_routes');
    }
};
