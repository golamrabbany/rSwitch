<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function __construct(
        private BalanceService $balanceService,
    ) {}

    public function create()
    {
        $clients = User::where('parent_id', auth()->id())
            ->where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'balance']);

        return view('reseller.balance.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'source' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $client = User::findOrFail($validated['user_id']);
        $reseller = auth()->user();

        // Ensure this is the reseller's own client
        if ($client->parent_id !== $reseller->id || $client->role !== 'client') {
            abort(403);
        }

        $amount = number_format((float) $validated['amount'], 4, '.', '');
        $notes = $validated['notes'] ?? '';
        $source = $validated['source'] ?? null;
        $remarks = $validated['remarks'] ?? null;

        // Check reseller has sufficient balance
        $reseller->refresh(); // Get fresh balance
        if (bccomp($reseller->balance, $amount, 4) < 0) {
            return back()->withErrors([
                'amount' => 'Insufficient balance. Your available balance is ' . format_currency($reseller->balance) . '. Please recharge your account first.',
            ])->withInput();
        }

        // Debit reseller first
        $this->balanceService->debit(
            user: $reseller,
            amount: $amount,
            type: 'transfer_out',
            referenceType: 'client_topup',
            description: "Transfer to client: {$client->name}",
            createdBy: $reseller->id,
            source: $source,
            remarks: "Client topup → {$client->name}",
        );

        // Credit client
        $transaction = $this->balanceService->credit(
            user: $client,
            amount: $amount,
            type: 'topup',
            referenceType: 'reseller_transfer',
            description: $notes ?: "Top-up from reseller: {$reseller->name}",
            createdBy: $reseller->id,
            source: $source,
            remarks: $remarks,
        );

        Payment::create([
            'user_id' => $client->id,
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => 'reseller_transfer',
            'recharged_by' => $reseller->id,
            'notes' => $notes ?: 'Reseller balance transfer',
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_id' => $transaction->id,
        ]);

        AuditService::logAction('balance.transfer', $client, [
            'amount' => $amount,
            'from_reseller' => $reseller->id,
            'reseller_balance_after' => $reseller->fresh()->balance,
            'client_balance_after' => $client->fresh()->balance,
            'transaction_id' => $transaction->id,
        ]);

        $resellerBalanceAfter = format_currency($reseller->fresh()->balance);

        return redirect()->route('reseller.clients.show', $client)
            ->with('success', "Transferred {$amount} to {$client->name}. Your remaining balance: {$resellerBalanceAfter}");
    }
}
