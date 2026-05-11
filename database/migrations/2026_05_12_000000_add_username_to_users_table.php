<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: previous attempts may have left the column or index in place
        // without recording the migration as run. Guard each step.

        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 100)->nullable()->after('email');
            });
        }

        $hasUniqueIndex = collect(DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_username_unique'"))->isNotEmpty();
        if (!$hasUniqueIndex) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username');
            });
        }

        // Back-fill: for each reseller/client TRIM(name), the OLDEST user_id
        // wins the username. Newer duplicates stay NULL — they keep
        // email-based login (synthetic or real). Group by the TRIMMED name so
        // rows differing only in whitespace collapse into one winner.
        DB::statement("
            UPDATE users u
            JOIN (
                SELECT MIN(id) AS first_id, TRIM(name) AS clean_name
                FROM users
                WHERE role IN ('reseller', 'client')
                  AND name IS NOT NULL
                  AND TRIM(name) <> ''
                  AND CHAR_LENGTH(TRIM(name)) <= 100
                GROUP BY TRIM(name)
            ) winners ON u.id = winners.first_id
            SET u.username = winners.clean_name
            WHERE u.username IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
