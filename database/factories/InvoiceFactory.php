<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $callCharges = fake()->randomFloat(4, 10, 500);
        $didCharges = fake()->randomFloat(4, 0, 50);

        return [
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('#####'),
            'user_id' => User::factory(),
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'call_charges' => $callCharges,
            'did_charges' => $didCharges,
            'total_amount' => bcadd((string) $callCharges, (string) $didCharges, 4),
            'tax_amount' => '0.0000',
            'status' => 'draft',
            'due_date' => now()->addDays(15)->toDateString(),
        ];
    }

    public function issued(): static
    {
        return $this->state(fn () => ['status' => 'issued']);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid', 'paid_at' => now()]);
    }
}
