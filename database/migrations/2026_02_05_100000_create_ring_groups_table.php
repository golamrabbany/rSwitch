<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ring_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('strategy', ['simultaneous', 'sequential', 'random'])->default('simultaneous');
            $table->unsignedInteger('ring_timeout')->default(30);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
        });

        Schema::create('ring_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ring_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sip_account_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('priority')->default(1);
            $table->unsignedInteger('delay')->default(0);
            $table->timestamps();

            $table->unique(['ring_group_id', 'sip_account_id']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ring_group_members');
        Schema::dropIfExists('ring_groups');
    }
};
