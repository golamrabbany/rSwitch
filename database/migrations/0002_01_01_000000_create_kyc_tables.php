<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('account_type', ['individual', 'company']);
            $table->string('full_name', 150);
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 20);
            $table->string('alt_phone', 20)->nullable();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20);
            $table->string('country', 2);
            $table->enum('id_type', ['national_id', 'passport', 'driving_license', 'business_license']);
            $table->string('id_number', 50);
            $table->date('id_expiry_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'id_front', 'id_back', 'selfie', 'proof_of_address',
                'business_registration', 'tax_certificate', 'other',
            ]);
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedInteger('file_size');
            $table->enum('status', ['uploaded', 'accepted', 'rejected'])->default('uploaded');
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('kyc_profiles');
    }
};
