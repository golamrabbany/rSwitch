<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sip_accounts', function (Blueprint $table) {
            $table->boolean('allow_p2p')->default(true)->after('codec_allow');
            $table->boolean('allow_recording')->default(false)->after('allow_p2p');
        });
    }

    public function down(): void
    {
        Schema::table('sip_accounts', function (Blueprint $table) {
            $table->dropColumn(['allow_p2p', 'allow_recording']);
        });
    }
};
