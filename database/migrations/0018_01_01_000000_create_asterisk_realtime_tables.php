<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ps_endpoints', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('transport', 40)->nullable();
            $table->string('aors', 200)->nullable();
            $table->string('auth', 40)->nullable();
            $table->string('context', 40)->default('from-internal');
            $table->string('disallow', 200)->default('all');
            $table->string('allow', 200)->default('ulaw,alaw,g729');
            $table->string('direct_media', 10)->default('no');
            $table->string('rtp_symmetric', 10)->default('yes');
            $table->string('force_rport', 10)->default('yes');
            $table->string('rewrite_contact', 10)->default('yes');
            $table->string('ice_support', 10)->default('no');
            $table->string('allow_transfer', 10)->default('yes');
            $table->unsignedInteger('max_audio_streams')->default(1);
            $table->unsignedInteger('device_state_busy_at')->default(0);
            $table->string('callerid', 80)->nullable();
        });

        Schema::create('ps_auths', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('auth_type', 20)->default('userpass');
            $table->string('username', 40)->nullable();
            $table->string('password', 80)->nullable();
        });

        Schema::create('ps_aors', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->unsignedInteger('max_contacts')->default(1);
            $table->unsignedInteger('qualify_frequency')->default(60);
            $table->string('remove_existing', 10)->default('yes');
        });

        Schema::create('ps_contacts', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->string('uri', 255)->nullable();
            $table->string('expiration_time', 40)->nullable();
            $table->string('qualify_frequency', 40)->nullable();
            $table->string('outbound_proxy', 255)->nullable();
            $table->string('path', 512)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('reg_server', 255)->nullable();
        });

        Schema::create('ps_endpoint_id_ips', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 40);
            $table->string('match', 80);

            $table->index('endpoint', 'idx_endpoint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ps_endpoint_id_ips');
        Schema::dropIfExists('ps_contacts');
        Schema::dropIfExists('ps_aors');
        Schema::dropIfExists('ps_auths');
        Schema::dropIfExists('ps_endpoints');
    }
};
