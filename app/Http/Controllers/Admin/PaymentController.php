<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('user', 'rechargedBy', 'invoice');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        $stats = [
            'completed' => Payment::where('status', 'completed')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments', 'users', 'stats'));
    }

    public function show(Payment $payment)
    {
        $payment->load('user', 'user.parent', 'rechargedBy', 'transaction', 'resellerTransaction', 'invoice');

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Refund a completed online payment.
     * Super Admin only. Optionally reverses reseller credit too.
     */
    public function refund(Request $request, Payment $payment, BalanceService $balanceService)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        // Only completed online payments can be refunded
        if ($payment->status !== 'completed') {
            return back()->with('error', 'Only completed payments can be refunded.');
        }

        if (!str_starts_with($payment->payment_method, 'online_')) {
            return back()->with('error', 'Only online payments can be refunded.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:' . $payment->amount],
            'refund_reseller' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = number_format((float) $validated['amount'], 4, '.', '');
        $refundReseller = (bool) ($validated['refund_reseller'] ?? false);
        $user = $payment->user;

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        DB::transaction(function () use ($user, $payment, $balanceService, $amount, $refundReseller, $validated) {
            // Step 1: Debit client
            $clientTxn = $balanceService->debit(
                user: $user,
                amount: $amount,
                type: 'refund',
                referenceType: 'payment',
                referenceId: $payment->id,
                description: "Refund: Payment #{$payment->id}",
                createdBy: auth()->id(),
            );

            // Step 2: Debit reseller (if checked and reseller exists)
            $resellerTxn = null;
            if ($refundReseller && $user->parent_id) {
                $reseller = User::find($user->parent_id);
                if ($reseller && $reseller->isReseller()) {
                    $resellerTxn = $balanceService->debit(
                        user: $reseller,
                        amount: $amount,
                        type: 'refund',
                        referenceType: 'payment',
                        referenceId: $payment->id,
                        description: "Refund: Client payment #{$payment->id} ({$user->name})",
                        createdBy: auth()->id(),
                    );
                }
            }

            // Update payment status
            $payment->update([
                'status' => 'refunded',
                'notes' => trim(($payment->notes ? $payment->notes . "\n" : '') .
                    'Refunded $' . $amount . ' by ' . auth()->user()->name .
                    ($resellerTxn ? ' (reseller also refunded)' : '') .
                    ($validated['notes'] ? ': ' . $validated['notes'] : '')),
            ]);

            AuditService::logAction('payment.refunded', $payment, [
                'amount' => $amount,
                'refund_reseller' => $refundReseller,
                'client_transaction_id' => $clientTxn->id,
                'reseller_transaction_id' => $resellerTxn?->id,
                'refunded_by' => auth()->id(),
            ]);
        });

        return back()->with('success', "Refunded \${$amount}" . ($refundReseller ? ' (client + reseller)' : ' (client only)') . '.');
    }
}
