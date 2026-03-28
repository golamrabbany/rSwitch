<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Services\BkashService;
use App\Services\PaymentCreditService;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::where('user_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $payments = $query->with('rechargedBy:id,name')
            ->orderByDesc('created_at')
            ->paginate(30);

        $stats = Payment::where('user_id', auth()->id())->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END), 0) as total_paid,
            COALESCE(SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END), 0) as total_pending
        ')->first();

        return view('reseller.payments.index', compact('payments', 'stats'));
    }

    public function create()
    {
        $gateways = collect([
            'sslcommerz' => (new SslCommerzService)->isEnabled(),
            'bkash' => (new BkashService)->isEnabled(),
        ])->filter()->keys();

        return view('reseller.payments.create', compact('gateways'));
    }

    /**
     * SSLCommerz Checkout.
     */
    public function checkoutSslCommerz(Request $request, SslCommerzService $sslcommerz)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:5', 'max:10000'],
        ]);

        $user = auth()->user();

        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'currency' => $user->currency ?? 'BDT',
            'payment_method' => 'online_sslcommerz',
            'status' => 'pending',
        ]);

        $payment->update(['gateway_transaction_id' => 'PAY-' . $payment->id]);

        $result = $sslcommerz->initiatePayment(
            $payment,
            $user,
            successUrl: route('webhook.sslcommerz.return', ['payment' => $payment->id, 'type' => 'success']),
            failUrl: route('webhook.sslcommerz.return', ['payment' => $payment->id, 'type' => 'fail']),
            cancelUrl: route('webhook.sslcommerz.return', ['payment' => $payment->id, 'type' => 'cancel']),
            ipnUrl: route('webhook.sslcommerz'),
        );

        if (($result['status'] ?? '') === 'SUCCESS' && !empty($result['GatewayPageURL'])) {
            return redirect($result['GatewayPageURL']);
        }

        $payment->update(['status' => 'failed', 'notes' => 'SSLCommerz init failed: ' . ($result['failedreason'] ?? 'unknown')]);
        return back()->with('error', 'Payment gateway error. Please try again.');
    }

    /**
     * bKash Checkout.
     */
    public function checkoutBkash(Request $request, BkashService $bkash)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:5', 'max:10000'],
        ]);

        $user = auth()->user();

        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'currency' => $user->currency ?? 'BDT',
            'payment_method' => 'online_bkash',
            'status' => 'pending',
        ]);

        $invoiceNumber = 'PAY-' . $payment->id;
        $payment->update(['gateway_transaction_id' => $invoiceNumber]);

        try {
            $result = $bkash->createPayment(
                (string) $validated['amount'],
                $user->currency ?? 'BDT',
                $invoiceNumber,
                route('reseller.payments.bkash-callback'),
            );

            if (!empty($result['bkashURL'])) {
                $payment->update([
                    'gateway_transaction_id' => $result['paymentID'],
                    'gateway_response' => $result,
                ]);
                return redirect($result['bkashURL']);
            }

            $payment->update(['status' => 'failed', 'notes' => 'bKash create failed: ' . ($result['statusMessage'] ?? 'unknown')]);
        } catch (\Throwable $e) {
            Log::error('bKash checkout error', ['error' => $e->getMessage()]);
            $payment->update(['status' => 'failed', 'notes' => 'bKash error: ' . $e->getMessage()]);
        }

        return back()->with('error', 'bKash payment error. Please try again.');
    }

    /**
     * bKash callback — execute payment and credit balance.
     */
    public function bkashCallback(Request $request, BkashService $bkash, PaymentCreditService $creditService)
    {
        $paymentId = $request->query('paymentID');
        $status = $request->query('status');

        $payment = Payment::where('gateway_transaction_id', $paymentId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$payment) {
            return redirect()->route('reseller.payments.create')->with('error', 'Payment not found.');
        }

        if ($payment->status === 'completed') {
            return view('reseller.payments.success', compact('payment'));
        }

        if ($status !== 'success') {
            $payment->update(['status' => 'failed', 'notes' => "bKash status: {$status}"]);
            return redirect()->route('reseller.payments.create')->with('error', 'Payment was cancelled or failed.');
        }

        try {
            $result = $bkash->executePayment($paymentId);

            if (($result['transactionStatus'] ?? '') === 'Completed' && (float) $result['amount'] == (float) $payment->amount) {
                $creditService->creditPayment($payment, $result);
                return view('reseller.payments.success', compact('payment'));
            }

            $payment->update(['status' => 'failed', 'gateway_response' => $result, 'notes' => 'bKash execute failed']);
        } catch (\Throwable $e) {
            Log::error('bKash execute error', ['error' => $e->getMessage()]);
            $payment->update(['status' => 'failed', 'notes' => 'bKash execute error']);
        }

        return redirect()->route('reseller.payments.create')->with('error', 'Payment verification failed.');
    }

    /**
     * Success return (Stripe / SSLCommerz).
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id') ?: $request->query('payment');
        $payment = Payment::where('gateway_transaction_id', $sessionId)
            ->where('user_id', auth()->id())
            ->first();

        return view('reseller.payments.success', compact('payment'));
    }
}
