<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change survey_response from VARCHAR(10) to TEXT for JSON storage
        DB::statement('ALTER TABLE broadcast_numbers MODIFY survey_response TEXT NULL');

        // Migrate existing single-digit responses to JSON format
        DB::statement("
            UPDATE broadcast_numbers
            SET survey_response = CONCAT('{\"q1\":\"', survey_response, '\"}')
            WHERE survey_response IS NOT NULL
            AND survey_response != ''
            AND LEFT(survey_response, 1) != '{'
        ");
    }

    public function down(): void
    {
        // Extract q1 value back
        DB::statement("
            UPDATE broadcast_numbers
            SET survey_response = JSON_UNQUOTE(JSON_EXTRACT(survey_response, '$.q1'))
            WHERE survey_response IS NOT NULL
            AND JSON_VALID(survey_response) = 1
        ");

        DB::statement('ALTER TABLE broadcast_numbers MODIFY survey_response VARCHAR(10) NULL');
    }
};
