<?php

namespace Tests\Unit\Services;

use App\Exceptions\Billing\RateNotFoundException;
use App\Models\Rate;
use App\Models\RateGroup;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RatingService $service;
    private RateGroup $rateGroup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RatingService();

        $admin = User::factory()->admin()->create();
        $this->rateGroup = RateGroup::factory()->create(['created_by' => $admin->id]);
    }

    public function test_find_rate_exact_prefix_match(): void
    {
        Rate::factory()->create([
            'rate_group_id' => $this->rateGroup->id,
            'prefix' => '4420',
            'destination' => 'UK London',
            'rate_per_minute' => '0.015000',
        ]);

        $rate = $this->service->findRate('44207890123', $this->rateGroup->id);

        $this->assertNotNull($rate);
        $this->assertEquals('4420', $rate->prefix);
        $this->assertEquals('UK London', $rate->destination);
    }

    public function test_find_rate_longest_prefix_wins(): void
    {
        Rate::factory()->create([
            'rate_group_id' => $this->rateGroup->id,
            'prefix' => '44',
            'destination' => 'UK General',
            'rate_per_minute' => '0.030000',
        ]);

        Rate::factory()->create([
            'rate_group_id' => $this->rateGroup->id,
            'prefix' => '4420',
            'destination' => 'UK London',
            'rate_per_minute' => '0.015000',
        ]);

        $rate = $this->service->findRate('44207890123', $this->rateGroup->id);

        $this->assertEquals('4420', $rate->prefix);
        $this->assertEquals('0.015000', $rate->rate_per_minute);
    }

    public function test_find_rate_returns_null_for_no_match(): void
    {
        Rate::factory()->create([
            'rate_group_id' => $this->rateGroup->id,
            'prefix' => '44',
            'destination' => 'UK',
        ]);

        $rate = $this->service->findRate('33123456789', $this->rateGroup->id);

        $this->assertNull($rate);
    }

    public function test_find_rate_skips_expired_rates(): void
    {
        Rate::factory()->expired()->create([
            'rate_group_id' => $this->rateGroup->id,
            'prefix' => '44',
            'rate_per_minute' => '0.010000',
        ]);

        $rate = $this->service->findRate('44207890123', $this->rateGroup->id);

        $this->assertNull($rate);
    }

    public function test_find_rate_skips_disabled_rates(): void
    {
        Rate::factory()->disabled()->create([
            'rate_group_id' => $this->rateGroup->id,
            'prefix' => '44',
        ]);

        $rate = $this->service->findRate('44207890123', $this->rateGroup->id);

        $this->assertNull($rate);
    }

    public function test_calculate_cost_basic(): void
    {
        $rate = Rate::factory()->make([
            'rate_per_minute' => '0.100000',
            'connection_fee' => '0.000000',
            'min_duration' => 0,
            'billing_increment' => 1,
        ]);

        $result = $this->service->calculateCost(60, $rate);

        $this->assertEquals(60, $result['billable_duration']);
        $this->assertEquals('0.1000', $result['total_cost']);
    }

    public function test_calculate_cost_with_billing_increment(): void
    {
        $rate = Rate::factory()->make([
            'rate_per_minute' => '0.120000',
            'connection_fee' => '0.000000',
            'min_duration' => 0,
            'billing_increment' => 6,
        ]);

        // 7 seconds rounds up to 12 (2 increments of 6)
        $result = $this->service->calculateCost(7, $rate);

        $this->assertEquals(12, $result['billable_duration']);
    }

    public function test_calculate_cost_with_min_duration(): void
    {
        $rate = Rate::factory()->make([
            'rate_per_minute' => '0.060000',
            'connection_fee' => '0.000000',
            'min_duration' => 30,
            'billing_increment' => 1,
        ]);

        // 5 seconds, but min_duration is 30
        $result = $this->service->calculateCost(5, $rate);

        $this->assertEquals(30, $result['billable_duration']);
        $this->assertEquals('0.0300', $result['total_cost']); // 30/60 * 0.06
    }

    public function test_calculate_cost_with_connection_fee(): void
    {
        $rate = Rate::factory()->make([
            'rate_per_minute' => '0.100000',
            'connection_fee' => '0.050000',
            'min_duration' => 0,
            'billing_increment' => 1,
        ]);

        $result = $this->service->calculateCost(60, $rate);

        $this->assertEquals('0.1500', $result['total_cost']); // 0.10 + 0.05
    }

    public function test_resolve_rates_throws_when_no_rate(): void
    {
        $this->expectException(RateNotFoundException::class);

        $this->service->resolveRates('99999999', $this->rateGroup->id);
    }

    public function test_resolve_rates_with_parent_group(): void
    {
        $parentGroup = RateGroup::factory()->create([
            'created_by' => User::factory()->admin()->create()->id,
        ]);
        $this->rateGroup->update(['parent_rate_group_id' => $parentGroup->id]);

        Rate::factory()->create([
            'rate_group_id' => $this->rateGroup->id,
            'prefix' => '44',
            'rate_per_minute' => '0.050000',
        ]);

        Rate::factory()->create([
            'rate_group_id' => $parentGroup->id,
            'prefix' => '44',
            'rate_per_minute' => '0.020000',
        ]);

        $rates = $this->service->resolveRates('44207890123', $this->rateGroup->id);

        $this->assertEquals('0.050000', $rates['sell']->rate_per_minute);
        $this->assertNotNull($rates['cost']);
        $this->assertEquals('0.020000', $rates['cost']->rate_per_minute);
    }
}
