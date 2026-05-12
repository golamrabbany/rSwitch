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
     * Browser return URL from SSLCommerz (cross-site POST).
     *
     * Routed with withoutMiddleware(StartSession::class) so the cross-site
     * POST — which doesn't carry the SameSite=Lax session cookie — doesn't
     * cause Laravel to issue a NEW empty session cookie that would overwrite
     * the user's auth cookie. Renders an inline page instead of redirecting
     * with a flash message; user clicks "Continue" to navigate back to the
     * panel with their existing auth intact.
     */
    public function returnUrl(Request $request)
    {
        $paymentId = $request->route('payment');
        $type = $request->route('type');

        $payment = Payment::find($paymentId);

        if (!$payment) {
            return view('payments.gateway-result', [
                'status' => 'error',
                'title' => 'Payment Not Found',
                'message' => 'We could not locate that payment.',
                'continueUrl' => '/',
            ]);
        }

        $user = $payment->user;
        $panel = $user?->isReseller() ? 'reseller' : 'client';
        $continueUrl = $panel === 'reseller' ? '/reseller/payments/create' : '/client/payments/create';

        if ($type === 'success') {
            return view('payments.gateway-result', [
                'status' => 'success',
                'title' => 'Payment Received',
                'message' => 'Thank you. Your balance will be credited within a moment.',
                'continueUrl' => $continueUrl,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ]);
        }

        if ($payment->status === 'pending') {
            $payment->update(['status' => 'failed', 'notes' => "SSLCommerz {$type}"]);
        }

        return view('payments.gateway-result', [
            'status' => $type === 'cancel' ? 'cancelled' : 'failed',
            'title' => $type === 'cancel' ? 'Payment Cancelled' : 'Payment Failed',
            'message' => $type === 'cancel'
                ? 'You cancelled the payment. No charge has been made.'
                : 'The payment did not complete. Please try again.',
            'continueUrl' => $continueUrl,
        ]);
    }
}
