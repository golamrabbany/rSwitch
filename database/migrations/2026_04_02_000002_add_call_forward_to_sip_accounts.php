<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sip_accounts', function (Blueprint $table) {
            $table->boolean('call_forward_enabled')->default(false)->after('allow_recording');
            $table->enum('call_forward_type', ['cfu', 'cfnr', 'cfb', 'cfnr_cfb'])->default('cfnr')->after('call_forward_enabled');
            $table->string('call_forward_destination', 50)->nullable()->after('call_forward_type');
            $table->unsignedSmallInteger('call_forward_timeout')->default(20)->after('call_forward_destination');
        });

        Schema::table('call_records', function (Blueprint $table) {
            $table->string('forwarded_from', 50)->nullable()->after('destination');
        });
    }

    public function down(): void
    {
        Schema::table('sip_accounts', function (Blueprint $table) {
            $table->dropColumn(['call_forward_enabled', 'call_forward_type', 'call_forward_destination', 'call_forward_timeout']);
        });

        Schema::table('call_records', function (Blueprint $table) {
            $table->dropColumn('forwarded_from');
        });
    }
};
