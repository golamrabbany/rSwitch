<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdr_summary_hourly', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->dateTime('hour_start');
            $table->unsignedInteger('total_calls')->default(0);
            $table->unsignedInteger('answered_calls')->default(0);
            $table->unsignedInteger('failed_calls')->default(0);
            $table->unsignedInteger('total_duration')->default(0);
            $table->unsignedInteger('total_billable')->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);
            $table->decimal('total_reseller_cost', 12, 4)->default(0);
            $table->decimal('asr', 5, 2)->nullable();
            $table->decimal('acd', 8, 2)->nullable();
            $table->timestamp('updated_at');

            $table->unique(['user_id', 'hour_start'], 'idx_user_hour');
            $table->index(['reseller_id', 'hour_start'], 'idx_reseller_hour');
        });

        Schema::create('cdr_summary_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->date('date');
            $table->unsignedInteger('total_calls')->default(0);
            $table->unsignedInteger('answered_calls')->default(0);
            $table->unsignedInteger('total_duration')->default(0);
            $table->unsignedInteger('total_billable')->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);
            $table->decimal('total_reseller_cost', 12, 4)->default(0);
            $table->decimal('asr', 5, 2)->nullable();
            $table->decimal('acd', 8, 2)->nullable();
            $table->timestamp('updated_at');

            $table->unique(['user_id', 'date'], 'idx_user_date');
            $table->index(['reseller_id', 'date'], 'idx_reseller_date');
        });

        Schema::create('cdr_summary_destination', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('matched_prefix', 20);
            $table->string('destination', 100);
            $table->unsignedBigInteger('outgoing_trunk_id')->nullable();
            $table->unsignedInteger('total_calls')->default(0);
            $table->unsignedInteger('answered_calls')->default(0);
            $table->unsignedInteger('total_duration')->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);
            $table->decimal('asr', 5, 2)->nullable();
            $table->decimal('acd', 8, 2)->nullable();
            $table->timestamp('updated_at');

            $table->unique(['date', 'matched_prefix', 'outgoing_trunk_id'], 'idx_date_prefix_trunk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdr_summary_destination');
        Schema::dropIfExists('cdr_summary_daily');
        Schema::dropIfExists('cdr_summary_hourly');
    }
};
