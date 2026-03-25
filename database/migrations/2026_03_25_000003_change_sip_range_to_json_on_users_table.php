<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing data to JSON format
        $users = DB::table('users')
            ->whereNotNull('sip_range_start')
            ->whereNotNull('sip_range_end')
            ->get(['id', 'sip_range_start', 'sip_range_end']);

        Schema::table('users', function (Blueprint $table) {
            $table->json('sip_ranges')->nullable()->after('max_channels');
        });

        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'sip_ranges' => json_encode([['start' => $user->sip_range_start, 'end' => $user->sip_range_end]]),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sip_range_start', 'sip_range_end']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sip_range_start', 20)->nullable()->after('max_channels');
            $table->string('sip_range_end', 20)->nullable()->after('sip_range_start');
            $table->dropColumn('sip_ranges');
        });
    }
};
