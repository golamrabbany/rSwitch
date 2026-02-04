<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trunks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('provider', 100);
            $table->enum('direction', ['incoming', 'outgoing', 'both']);
            $table->string('host');
            $table->unsignedInteger('port')->default(5060);
            $table->string('username', 100)->nullable();
            $table->string('password', 100)->nullable();
            $table->boolean('register')->default(false);
            $table->string('register_string')->nullable();
            $table->enum('transport', ['udp', 'tcp', 'tls'])->default('udp');
            $table->string('codec_allow', 100)->default('ulaw,alaw,g729');
            $table->unsignedInteger('max_channels')->default(30);
            $table->unsignedInteger('outgoing_priority')->default(1);

            // Dial string manipulation
            $table->string('dial_pattern_match', 50)->nullable();
            $table->string('dial_pattern_replace', 50)->nullable();
            $table->string('dial_prefix', 20)->nullable();
            $table->unsignedInteger('dial_strip_digits')->default(0);
            $table->string('tech_prefix', 20)->nullable();

            // CLI manipulation
            $table->enum('cli_mode', ['passthrough', 'override', 'prefix_strip', 'translate', 'hide'])->default('passthrough');
            $table->string('cli_override_number', 40)->nullable();
            $table->unsignedInteger('cli_prefix_strip')->default(0);
            $table->string('cli_prefix_add', 20)->nullable();

            // Incoming settings
            $table->string('incoming_context', 80)->default('from-trunk');
            $table->enum('incoming_auth_type', ['ip', 'registration', 'both'])->default('ip');
            $table->string('incoming_ip_acl')->nullable();

            // Health monitoring
            $table->boolean('health_check')->default(true);
            $table->unsignedInteger('health_check_interval')->default(60);
            $table->enum('health_status', ['up', 'down', 'degraded', 'unknown'])->default('unknown');
            $table->timestamp('health_last_checked_at')->nullable();
            $table->timestamp('health_last_up_at')->nullable();
            $table->unsignedInteger('health_fail_count')->default(0);
            $table->unsignedInteger('health_auto_disable_threshold')->default(5);
            $table->decimal('health_asr_threshold', 5, 2)->nullable();

            $table->enum('status', ['active', 'disabled', 'auto_disabled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['direction', 'status'], 'idx_direction');
            $table->index('outgoing_priority', 'idx_outgoing_priority');
            $table->index('health_status', 'idx_health');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trunks');
    }
};
