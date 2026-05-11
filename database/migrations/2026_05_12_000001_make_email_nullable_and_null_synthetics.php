<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make email nullable. The unique index stays valid because MySQL
        // treats each NULL as distinct (multiple NULLs allowed in unique).
        // Using raw ALTER preserves the existing unique index without needing
        // doctrine/dbal's column-change machinery.
        DB::statement("ALTER TABLE users MODIFY email VARCHAR(255) NULL");

        // Replace synthetic placeholder emails with NULL so the data reflects
        // reality (these accounts have no email on file).
        DB::table('users')
            ->where('email', 'like', '%@weblink.import')
            ->update(['email' => null]);
    }

    public function down(): void
    {
        // Restore synthetic emails so the NOT NULL constraint can be re-applied
        // without violating it. Uses the same naming convention as the importer.
        DB::statement("
            UPDATE users
            SET email = CONCAT('user_', id, '@weblink.import')
            WHERE email IS NULL
        ");

        DB::statement("ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL");
    }
};
