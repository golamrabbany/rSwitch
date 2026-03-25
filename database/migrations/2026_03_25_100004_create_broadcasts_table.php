<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('the client');
            $table->foreignId('sip_account_id')->constrained();
            $table->unsignedBigInteger('voice_file_id');
            $table->enum('type', ['simple', 'survey']);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('status', [
                'draft', 'scheduled', 'queued', 'running',
                'paused', 'completed', 'cancelled', 'failed',
            ])->default('draft');
            $table->string('caller_id_name', 40)->nullable();
            $table->string('caller_id_number', 20)->nullable();
            $table->unsignedTinyInteger('max_concurrent')->default(5);
            $table->unsignedTinyInteger('retry_attempts')->default(1);
            $table->unsignedSmallInteger('retry_delay')->default(300)->comment('seconds');
            $table->unsignedTinyInteger('ring_timeout')->default(30)->comment('seconds');
            $table->json('survey_config')->nullable();
            $table->enum('phone_list_type', ['manual', 'csv']);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('total_numbers')->default(0);
            $table->unsignedInteger('dialed_count')->default(0);
            $table->unsignedInteger('answered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('voice_file_id')->references('id')->on('voice_files');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
