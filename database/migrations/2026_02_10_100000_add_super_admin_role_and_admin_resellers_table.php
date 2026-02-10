<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify role enum to add 'super_admin'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'reseller', 'client') NOT NULL");

        // Update existing admin to super_admin (first admin becomes super admin)
        $firstAdmin = DB::table('users')->where('role', 'admin')->orderBy('id')->first();
        if ($firstAdmin) {
            DB::table('users')->where('id', $firstAdmin->id)->update(['role' => 'super_admin']);
        }

        // Create admin_resellers pivot table for many-to-many relationship
        Schema::create('admin_resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reseller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_id', 'reseller_id']);
            $table->index('admin_id');
            $table->index('reseller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_resellers');

        // Revert super_admin back to admin
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);

        // Revert role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'reseller', 'client') NOT NULL");
    }
};
