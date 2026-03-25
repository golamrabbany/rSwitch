<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 20);
            $table->enum('status', [
                'pending', 'queued', 'dialing', 'answered',
                'no_answer', 'busy', 'failed', 'completed',
            ])->default('pending');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('answered_at')->nullable();
            $table->unsignedInteger('duration')->default(0)->comment('seconds');
            $table->decimal('cost', 10, 4)->default(0);
            $table->string('survey_response', 10)->nullable();
            $table->unsignedBigInteger('call_record_id')->nullable();
            $table->string('error_reason', 100)->nullable();
            $table->timestamps();

            $table->index(['broadcast_id', 'status']);
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_numbers');
    }
};
