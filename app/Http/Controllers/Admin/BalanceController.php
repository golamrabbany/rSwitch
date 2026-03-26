<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BalanceController extends Controller
{
    public function __construct(
        private BalanceService $balanceService,
    ) {}

    public function create()
    {
        $users = User::whereIn('role', ['reseller', 'client'])
            ->with(['parent:id,name,role'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'balance', 'role', 'parent_id']);

        return view('admin.balance.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'operation' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'source' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'adjust_reseller' => ['nullable', 'boolean'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $amount = number_format((float) $validated['amount'], 4, '.', '');
        $notes = $validated['notes'] ?? '';
        $source = $validated['source'] ?? null;
        $remarks = $validated['remarks'] ?? null;
        $adjustReseller = (bool) ($validated['adjust_reseller'] ?? false);

        // Find parent reseller if checkbox was checked
        $reseller = null;
        if ($adjustReseller && $user->parent_id) {
            $reseller = User::find($user->parent_id);
            if (!$reseller || !$reseller->isReseller()) {
                $reseller = null;
            }
        }

        if ($validated['operation'] === 'credit') {
            $transaction = $this->balanceService->credit(
                user: $user,
                amount: $amount,
                type: 'topup',
                referenceType: 'manual_admin',
                description: $notes ?: "Admin topup by " . auth()->user()->name,
                createdBy: auth()->id(),
                source: $source,
                remarks: $remarks,
            );

            // Also credit parent reseller
            $resellerTxn = null;
            if ($reseller) {
                $resellerTxn = $this->balanceService->credit(
                    user: $reseller,
                    amount: $amount,
                    type: 'client_payment',
                    referenceType: 'manual_admin',
                    description: "Client topup ({$user->name}) by " . auth()->user()->name,
                    createdBy: auth()->id(),
                    source: $source,
                    remarks: $remarks,
                );
            }

            Payment::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => 'USD',
                'payment_method' => 'manual_admin',
                'recharged_by' => auth()->id(),
                'notes' => $notes ?: 'Admin manual topup',
                'status' => 'completed',
                'completed_at' => now(),
                'transaction_id' => $transaction->id,
                'reseller_transaction_id' => $resellerTxn?->id,
            ]);

            AuditService::logAction('balance.credit', $user, [
                'amount' => $amount,
                'notes' => $notes,
                'transaction_id' => $transaction->id,
                'reseller_credited' => $reseller ? true : false,
            ]);

            $msg = "Credited \${$amount} to {$user->name}.";
            if ($reseller) {
                $msg .= " Also credited to reseller {$reseller->name}.";
            }

            return redirect()->route('admin.transactions.index', ['user_id' => $user->id])
                ->with('success', $msg);
        }

        $transaction = $this->balanceService->debit(
            user: $user,
            amount: $amount,
            type: 'adjustment',
            referenceType: 'manual_admin',
            description: $notes ?: "Admin debit by " . auth()->user()->name,
            createdBy: auth()->id(),
            source: $source,
            remarks: $remarks,
        );

        // Also debit parent reseller
        if ($reseller) {
            $this->balanceService->debit(
                user: $reseller,
                amount: $amount,
                type: 'adjustment',
                referenceType: 'manual_admin',
                description: "Client debit ({$user->name}) by " . auth()->user()->name,
                createdBy: auth()->id(),
                source: $source,
                remarks: $remarks,
            );
        }

        AuditService::logAction('balance.debit', $user, [
            'amount' => $amount,
            'notes' => $notes,
            'transaction_id' => $transaction->id,
            'reseller_debited' => $reseller ? true : false,
        ]);

        $msg = "Debited \${$amount} from {$user->name}.";
        if ($reseller) {
            $msg .= " Also debited from reseller {$reseller->name}.";
        }

        return redirect()->route('admin.transactions.index', ['user_id' => $user->id])
            ->with('success', $msg);
    }
}
