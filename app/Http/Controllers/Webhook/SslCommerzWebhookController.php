<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentCreditService;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SslCommerzWebhookController extends Controller
{
    /**
     * Handle SSLCommerz IPN (Instant Payment Notification).
     */
    public function handle(Request $request, SslCommerzService $sslcommerz, PaymentCreditService $creditService)
    {
        $tranId = $request->input('tran_id');
        $valId = $request->input('val_id');
        $status = $request->input('status');

        Log::info('SSLCommerz IPN received', ['tran_id' => $tranId, 'status' => $status]);

        $payment = Payment::where('gateway_transaction_id', $tranId)->first();

        if (!$payment) {
            Log::warning('SSLCommerz IPN: payment not found', ['tran_id' => $tranId]);
            return response('Payment not found', 200);
        }

        if ($payment->status === 'completed') {
            return response('Already processed', 200);
        }

        if ($status !== 'VALID' && $status !== 'VALIDATED') {
            $payment->update(['status' => 'failed', 'notes' => "SSLCommerz status: {$status}", 'gateway_response' => $request->all()]);
            return response('OK', 200);
        }

        // Server-side validation
        $validation = $sslcommerz->validatePayment($valId);

        if (!in_array($validation['status'] ?? '', ['VALID', 'VALIDATED'])) {
            Log::warning('SSLCommerz validation failed', ['val_id' => $valId, 'response' => $validation]);
            $payment->update(['status' => 'failed', 'notes' => 'SSLCommerz validation failed', 'gateway_response' => $validation]);
            return response('Validation failed', 200);
        }

        // Amount verification
        if ((float) ($validation['amount'] ?? 0) != (float) $payment->amount) {
            Log::warning('SSLCommerz amount mismatch', ['expected' => $payment->amount, 'got' => $validation['amount'] ?? 0]);
            $payment->update(['status' => 'failed', 'notes' => 'Amount mismatch']);
            return response('Amount mismatch', 200);
        }

        $creditService->creditPayment($payment, $validation);

        return response('OK', 200);
    }

    /**
     * Handle SSLCommerz success/fail/cancel redirects.
     * These are user-facing redirects (POST from SSLCommerz gateway).
     */
    public function returnUrl(Request $request)
    {
        $paymentId = $request->route('payment');
        $type = $request->route('type');

        $payment = Payment::find($paymentId);

        if (!$payment) {
            return redirect('/')->with('error', 'Payment not found.');
        }

        // Determine which panel to redirect to
        $user = $payment->user;
        $panel = $user?->isReseller() ? 'reseller' : 'client';

        if ($type === 'success') {
            // Don't credit here — IPN handles that. Just show success page.
            if ($panel === 'reseller') {
                return view('reseller.payments.success', compact('payment'));
            }
            return view('client.payments.success', compact('payment'));
        }

        // Fail or cancel
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'failed', 'notes' => "SSLCommerz {$type}"]);
        }

        $route = $panel === 'reseller' ? 'reseller.payments.create' : 'client.payments.create';
        return redirect()->route($route)->with('error', $type === 'cancel' ? 'Payment cancelled.' : 'Payment failed. Please try again.');
    }
}
