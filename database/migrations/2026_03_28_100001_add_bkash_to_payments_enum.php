<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
            'online_stripe', 'online_paypal', 'online_sslcommerz', 'online_bkash',
            'bank_transfer', 'manual_admin', 'manual_reseller', 'reseller_transfer', 'recharge_admin'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
            'online_stripe', 'online_paypal', 'online_sslcommerz',
            'bank_transfer', 'manual_admin', 'manual_reseller', 'reseller_transfer', 'recharge_admin'
        ) NOT NULL");
    }
};
