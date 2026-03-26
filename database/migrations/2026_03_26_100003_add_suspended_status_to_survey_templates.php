<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE survey_templates MODIFY status ENUM('draft','pending','approved','rejected','suspended') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE survey_templates SET status = 'rejected' WHERE status = 'suspended'");
        DB::statement("ALTER TABLE survey_templates MODIFY status ENUM('draft','pending','approved','rejected') DEFAULT 'draft'");
    }
};
