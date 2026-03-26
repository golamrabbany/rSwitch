<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add rate_group_id to trunks — the provider's rate card
        Schema::table('trunks', function (Blueprint $table) {
            $table->unsignedBigInteger('rate_group_id')->nullable()->after('max_channels');
            $table->foreign('rate_group_id')->references('id')->on('rate_groups')->nullOnDelete();
        });

        // Add trunk_cost to call_records — what platform pays the trunk provider per call
        // Using raw SQL because call_records is partitioned
        DB::statement("ALTER TABLE call_records ADD COLUMN trunk_cost DECIMAL(10,4) NOT NULL DEFAULT 0 AFTER reseller_cost");

        // Add trunk_cost to cdr_summary_daily
        Schema::table('cdr_summary_daily', function (Blueprint $table) {
            $table->decimal('total_trunk_cost', 12, 4)->default(0)->after('total_reseller_cost');
        });

        // Add trunk_cost to cdr_summary_hourly
        Schema::table('cdr_summary_hourly', function (Blueprint $table) {
            $table->decimal('total_trunk_cost', 12, 4)->default(0)->after('total_reseller_cost');
        });
    }

    public function down(): void
    {
        Schema::table('trunks', function (Blueprint $table) {
            $table->dropForeign(['rate_group_id']);
            $table->dropColumn('rate_group_id');
        });

        DB::statement("ALTER TABLE call_records DROP COLUMN trunk_cost");

        Schema::table('cdr_summary_daily', function (Blueprint $table) {
            $table->dropColumn('total_trunk_cost');
        });

        Schema::table('cdr_summary_hourly', function (Blueprint $table) {
            $table->dropColumn('total_trunk_cost');
        });
    }
};
