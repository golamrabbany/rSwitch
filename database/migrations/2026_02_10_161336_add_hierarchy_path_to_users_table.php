<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Materialized path for efficient hierarchy queries
            // Format: /1/5/12/ where numbers are user IDs from root to self
            $table->string('hierarchy_path', 500)->nullable()->after('parent_id');
            $table->index('hierarchy_path');
        });

        // Populate hierarchy paths for existing users
        $this->populateHierarchyPaths();
    }

    /**
     * Populate hierarchy paths for all existing users.
     */
    protected function populateHierarchyPaths(): void
    {
        // First, update root-level users (no parent)
        DB::table('users')
            ->whereNull('parent_id')
            ->update(['hierarchy_path' => DB::raw("CONCAT('/', id, '/')")]);

        // Then update users with parents (resellers' clients)
        // Using a join to get parent's hierarchy_path
        $maxIterations = 10;
        for ($i = 0; $i < $maxIterations; $i++) {
            $updated = DB::statement("
                UPDATE users u
                INNER JOIN users p ON u.parent_id = p.id
                SET u.hierarchy_path = CONCAT(p.hierarchy_path, u.id, '/')
                WHERE u.hierarchy_path IS NULL
                AND p.hierarchy_path IS NOT NULL
            ");

            // Check if any rows still need updating
            $remaining = DB::table('users')
                ->whereNull('hierarchy_path')
                ->whereNotNull('parent_id')
                ->count();

            if ($remaining === 0) {
                break;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['hierarchy_path']);
            $table->dropColumn('hierarchy_path');
        });
    }
};
