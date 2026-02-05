<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('secret', 64);
            $table->json('events'); // ["call.completed", "payment.received", ...]
            $table->boolean('active')->default(true);
            $table->string('description')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->onDelete('cascade');
            $table->string('event');
            $table->json('payload');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index(['webhook_endpoint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhook_endpoints');
    }
};
