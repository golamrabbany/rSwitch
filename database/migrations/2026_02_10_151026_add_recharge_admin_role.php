<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add recharge_admin to the role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role
            ENUM('super_admin', 'admin', 'recharge_admin', 'reseller', 'client') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove recharge_admin from the role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role
            ENUM('super_admin', 'admin', 'reseller', 'client') NOT NULL");
    }
};
