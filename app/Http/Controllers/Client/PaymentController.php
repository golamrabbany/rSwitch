<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function create()
    {
        return view('client.payments.create');
    }

    /**
     * Create a Stripe Checkout session for balance top-up.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:5', 'max:10000'],
        ]);

        $user = auth()->user();
        $amount = $validated['amount'];
        $amountCents = (int) round($amount * 100);

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($user->currency ?? 'usd'),
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => 'Balance Top-Up',
                        'description' => "Add \${$amount} to your rSwitch account",
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('client.payments.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('client.payments.create'),
            'client_reference_id' => (string) $user->id,
            'metadata' => [
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'balance_topup',
            ],
        ]);

        // Create pending payment record
        Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $user->currency ?? 'USD',
            'payment_method' => 'online_stripe',
            'gateway_transaction_id' => $session->id,
            'status' => 'pending',
            'notes' => 'Stripe Checkout session created',
        ]);

        return redirect($session->url);
    }

    /**
     * Success return from Stripe (display only — actual crediting happens via webhook).
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        $payment = Payment::where('gateway_transaction_id', $sessionId)
            ->where('user_id', auth()->id())
            ->first();

        return view('client.payments.success', compact('payment'));
    }
}
