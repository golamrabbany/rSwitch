<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dnc_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique();
            $table->string('reason', 100)->nullable();
            $table->unsignedBigInteger('added_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('added_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dnc_numbers');
    }
};
