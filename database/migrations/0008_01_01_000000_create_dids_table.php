<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dids', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->string('provider', 100);
            $table->foreignId('trunk_id')->constrained();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->enum('destination_type', ['sip_account', 'ring_group', 'external']);
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->decimal('monthly_cost', 8, 4);
            $table->decimal('monthly_price', 8, 4);
            $table->enum('status', ['active', 'unassigned', 'disabled'])->default('unassigned');
            $table->timestamps();

            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dids');
    }
};
