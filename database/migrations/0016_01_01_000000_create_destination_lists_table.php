<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20);
            $table->string('description', 200);
            $table->enum('applies_to', ['all', 'specific_users']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at');

            $table->index('prefix', 'idx_prefix');
        });

        Schema::create('destination_whitelist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('prefix', 20);
            $table->string('description', 200);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at');

            $table->index(['user_id', 'prefix'], 'idx_user_prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_whitelist');
        Schema::dropIfExists('destination_blacklist');
    }
};
