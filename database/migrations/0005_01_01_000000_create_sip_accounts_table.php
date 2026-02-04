<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sip_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('username', 40)->unique();
            $table->string('password', 80);
            $table->enum('auth_type', ['password', 'ip', 'both'])->default('password');
            $table->string('allowed_ips', 500)->nullable();
            $table->string('caller_id_name', 80);
            $table->string('caller_id_number', 20);
            $table->unsignedInteger('max_channels')->default(2);
            $table->string('codec_allow', 100)->default('ulaw,alaw,g729');
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');
            $table->timestamp('last_registered_at')->nullable();
            $table->string('last_registered_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sip_accounts');
    }
};
