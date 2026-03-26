<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('creator');
            $table->foreignId('client_id')->nullable()->constrained('users')->comment('target client');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->json('config')->nullable()->comment('v2 survey config with questions, options');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index('status');
        });

        // Add survey_template_id to broadcasts table
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_template_id')->nullable()->after('voice_file_id');
            $table->foreign('survey_template_id')->references('id')->on('survey_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->dropForeign(['survey_template_id']);
            $table->dropColumn('survey_template_id');
        });
        Schema::dropIfExists('survey_templates');
    }
};
