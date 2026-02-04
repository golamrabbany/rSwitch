<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'reseller', 'client']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');

            // KYC
            $table->enum('kyc_status', ['not_submitted', 'pending', 'approved', 'rejected'])->default('not_submitted');
            $table->timestamp('kyc_verified_at')->nullable();
            $table->string('kyc_rejected_reason')->nullable();

            // Billing
            $table->enum('billing_type', ['prepaid', 'postpaid'])->default('prepaid');
            $table->decimal('credit_limit', 12, 4)->default(0);
            $table->decimal('balance', 12, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('rate_group_id')->nullable();
            $table->decimal('min_balance_for_calls', 10, 4)->default(0.00);
            $table->decimal('low_balance_threshold', 10, 4)->default(5.00);

            // Limits & fraud prevention
            $table->unsignedInteger('max_channels')->default(10);
            $table->decimal('daily_spend_limit', 10, 4)->nullable();
            $table->unsignedInteger('daily_call_limit')->nullable();
            $table->boolean('destination_whitelist_enabled')->default(false);

            // 2FA
            $table->boolean('two_fa_enabled')->default(false);
            $table->string('two_fa_secret')->nullable();
            $table->json('two_fa_recovery_codes')->nullable();
            $table->timestamp('two_fa_confirmed_at')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
