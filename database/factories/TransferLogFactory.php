<?php

namespace Database\Factories;

use App\Models\TransferLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TransferLog> */
class TransferLogFactory extends Factory
{
    protected $model = TransferLog::class;

    public function definition(): array
    {
        return [
            'transfer_type' => 'sip_account',
            'transferred_item_id' => 1,
            'transferred_item_type' => 'App\\Models\\SipAccount',
            'from_parent_id' => User::factory()->reseller(),
            'to_parent_id' => User::factory()->reseller(),
            'performed_by' => User::factory()->admin(),
            'created_at' => now(),
        ];
    }
}
