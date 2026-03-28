<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslCommerzService
{
    private string $storeId;
    private string $storePassword;
    private string $baseUrl;

    public function __construct()
    {
        $this->storeId = SystemSetting::get('sslcommerz_store_id', '');
        $this->storePassword = SystemSetting::get('sslcommerz_store_password', '');
        $sandbox = SystemSetting::get('sslcommerz_sandbox', true);
        $this->baseUrl = $sandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    public function isEnabled(): bool
    {
        return SystemSetting::get('sslcommerz_enabled', false)
            && !empty($this->storeId)
            && !empty($this->storePassword);
    }

    /**
     * Initiate a payment session with SSLCommerz.
     * Returns ['status' => 'SUCCESS', 'GatewayPageURL' => '...'] on success.
     */
    public function initiatePayment(Payment $payment, User $user, string $successUrl, string $failUrl, string $cancelUrl, string $ipnUrl): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/gwprocess/v4/api.php", [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => (float) $payment->amount,
            'currency' => $payment->currency ?: 'BDT',
            'tran_id' => 'PAY-' . $payment->id,
            'success_url' => $successUrl,
            'fail_url' => $failUrl,
            'cancel_url' => $cancelUrl,
            'ipn_url' => $ipnUrl,
            'shipping_method' => 'NO',
            'product_name' => 'Balance Top-Up',
            'product_category' => 'digital',
            'product_profile' => 'general',
            'cus_name' => $user->name,
            'cus_email' => $user->email ?: 'customer@example.com',
            'cus_add1' => 'N/A',
            'cus_city' => 'N/A',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $user->phone ?: '01700000000',
        ]);

        $data = $response->json();

        Log::info('SSLCommerz initiatePayment', [
            'payment_id' => $payment->id,
            'status' => $data['status'] ?? 'unknown',
        ]);

        return $data ?? [];
    }

    /**
     * Validate a payment with SSLCommerz server (IPN verification).
     */
    public function validatePayment(string $valId): array
    {
        $url = "{$this->baseUrl}/validator/api/validationserverAPI.php";

        $response = Http::get($url, [
            'val_id' => $valId,
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'format' => 'json',
        ]);

        $data = $response->json();

        Log::info('SSLCommerz validatePayment', [
            'val_id' => $valId,
            'status' => $data['status'] ?? 'unknown',
        ]);

        return $data ?? [];
    }
}
