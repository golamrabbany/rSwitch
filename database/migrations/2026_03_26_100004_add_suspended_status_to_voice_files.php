<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE voice_files MODIFY status ENUM('pending','approved','rejected','suspended') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE voice_files SET status = 'rejected' WHERE status = 'suspended'");
        DB::statement("ALTER TABLE voice_files MODIFY status ENUM('pending','approved','rejected') DEFAULT 'pending'");
    }
};
