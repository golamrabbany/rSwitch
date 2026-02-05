<?php

namespace Database\Factories;

use App\Models\RateGroup;
use App\Models\RateImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RateImport> */
class RateImportFactory extends Factory
{
    protected $model = RateImport::class;

    public function definition(): array
    {
        return [
            'rate_group_id' => RateGroup::factory(),
            'uploaded_by' => User::factory()->admin(),
            'file_name' => 'rates_' . fake()->date('Ymd') . '.csv',
            'file_path' => 'imports/' . fake()->uuid() . '.csv',
            'total_rows' => fake()->numberBetween(100, 5000),
            'imported_rows' => fake()->numberBetween(90, 4900),
            'skipped_rows' => fake()->numberBetween(0, 50),
            'error_rows' => fake()->numberBetween(0, 10),
            'status' => 'completed',
            'completed_at' => now(),
        ];
    }
}
