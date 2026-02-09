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

        // Ensure this is the reseller's own client
        if ($client->parent_id !== auth()->id() || $client->role !== 'client') {
            abort(403);
        }

        $amount = number_format((float) $validated['amount'], 4, '.', '');
        $notes = $validated['notes'] ?? '';
        $source = $validated['source'] ?? null;
        $remarks = $validated['remarks'] ?? null;

        $transaction = $this->balanceService->credit(
            user: $client,
            amount: $amount,
            type: 'topup',
            referenceType: 'manual_reseller',
            description: $notes ?: "Reseller topup by " . auth()->user()->name,
            createdBy: auth()->id(),
            source: $source,
            remarks: $remarks,
        );

        Payment::create([
            'user_id' => $client->id,
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => 'manual_reseller',
            'recharged_by' => auth()->id(),
            'notes' => $notes ?: 'Reseller manual topup',
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_id' => $transaction->id,
        ]);

        AuditService::logAction('balance.credit', $client, [
            'amount' => $amount,
            'notes' => $notes,
            'by_reseller' => auth()->id(),
            'transaction_id' => $transaction->id,
        ]);

        return redirect()->route('reseller.transactions.index', ['user_id' => $client->id])
            ->with('success', "Credited \${$amount} to {$client->name}.");
    }
}
