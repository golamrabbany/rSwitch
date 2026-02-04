<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('type', ['admin', 'reseller'])->default('admin');
            $table->unsignedBigInteger('parent_rate_group_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->foreign('parent_rate_group_id')->references('id')->on('rate_groups')->nullOnDelete();
        });

        // Now add the FK from users to rate_groups
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('rate_group_id')->references('id')->on('rate_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rate_group_id']);
        });
        Schema::dropIfExists('rate_groups');
    }
};
