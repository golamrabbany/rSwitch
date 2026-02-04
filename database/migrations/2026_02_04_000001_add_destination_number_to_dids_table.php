<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dids', function (Blueprint $table) {
            $table->string('destination_number', 30)->nullable()->after('destination_id');
        });
    }

    public function down(): void
    {
        Schema::table('dids', function (Blueprint $table) {
            $table->dropColumn('destination_number');
        });
    }
};
