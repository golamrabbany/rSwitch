<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BkashService
{
    private string $appKey;
    private string $appSecret;
    private string $username;
    private string $password;
    private string $baseUrl;

    public function __construct()
    {
        $this->appKey = SystemSetting::get('bkash_app_key', '');
        $this->appSecret = SystemSetting::get('bkash_app_secret', '');
        $this->username = SystemSetting::get('bkash_username', '');
        $this->password = SystemSetting::get('bkash_password', '');
        $sandbox = SystemSetting::get('bkash_sandbox', true);
        $this->baseUrl = $sandbox
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function isEnabled(): bool
    {
        return SystemSetting::get('bkash_enabled', false)
            && !empty($this->appKey)
            && !empty($this->appSecret);
    }

    /**
     * Get or refresh the bKash auth token.
     */
    public function grantToken(): string
    {
        $cached = Cache::get('bkash_token');
        if ($cached) {
            return $cached;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'username' => $this->username,
            'password' => $this->password,
        ])->post("{$this->baseUrl}/tokenized/checkout/token/grant", [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
        ]);

        $data = $response->json();

        if (!empty($data['id_token'])) {
            // Cache for 55 minutes (token typically valid for 60 min)
            Cache::put('bkash_token', $data['id_token'], 3300);
            return $data['id_token'];
        }

        Log::error('bKash grantToken failed', ['response' => $data]);
        throw new \RuntimeException('Failed to obtain bKash token: ' . ($data['statusMessage'] ?? 'unknown error'));
    }

    /**
     * Create a bKash payment.
     * Returns ['bkashURL' => '...', 'paymentID' => '...'] on success.
     */
    public function createPayment(string $amount, string $currency, string $invoiceNumber, string $callbackUrl): array
    {
        $token = $this->grantToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $token,
            'X-APP-Key' => $this->appKey,
        ])->post("{$this->baseUrl}/tokenized/checkout/create", [
            'mode' => '0011',
            'payerReference' => $invoiceNumber,
            'callbackURL' => $callbackUrl,
            'amount' => $amount,
            'currency' => $currency ?: 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceNumber,
        ]);

        $data = $response->json();

        Log::info('bKash createPayment', [
            'invoice' => $invoiceNumber,
            'status' => $data['statusCode'] ?? $data['statusMessage'] ?? 'unknown',
        ]);

        return $data ?? [];
    }

    /**
     * Execute a bKash payment after customer authorization.
     */
    public function executePayment(string $paymentId): array
    {
        $token = $this->grantToken();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $token,
            'X-APP-Key' => $this->appKey,
        ])->post("{$this->baseUrl}/tokenized/checkout/execute", [
            'paymentID' => $paymentId,
        ]);

        $data = $response->json();

        Log::info('bKash executePayment', [
            'paymentID' => $paymentId,
            'status' => $data['transactionStatus'] ?? $data['statusMessage'] ?? 'unknown',
        ]);

        return $data ?? [];
    }
}
