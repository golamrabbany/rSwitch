<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Contact Information
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('alt_phone', 20)->nullable()->after('phone');
            $table->string('address', 255)->nullable()->after('alt_phone');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('state');
            $table->string('zip_code', 20)->nullable()->after('country');

            // Company Details
            $table->string('company_name', 200)->nullable()->after('zip_code');
            $table->string('company_website', 255)->nullable()->after('company_name');
            $table->text('notes')->nullable()->after('company_website');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'alt_phone', 'address', 'city', 'state', 'country', 'zip_code',
                'company_name', 'company_website', 'notes',
            ]);
        });
    }
};
