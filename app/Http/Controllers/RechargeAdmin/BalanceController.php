<?php

namespace App\Http\Controllers\RechargeAdmin;

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

    public function create(Request $request)
    {
        $currentUser = auth()->user();
        $resellerIds = $currentUser->assignedResellers()->pluck('users.id')->toArray();
        $clientIds = User::whereIn('parent_id', $resellerIds)->pluck('id')->toArray();

        $users = User::whereIn('id', array_merge($resellerIds, $clientIds))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'balance', 'role']);

        // Pre-select user if provided
        $selectedUserId = $request->query('user_id');

        return view('recharge-admin.balance.create', compact('users', 'selectedUserId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'operation' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'reason' => ['required', 'string', 'min:5', 'max:500'], // MANDATORY
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        // Authorization check
        abort_unless(auth()->user()->canRechargeBalance($targetUser), 403, 'Not authorized to recharge this user.');

        $previousBalance = $targetUser->balance;
        $amount = number_format((float) $validated['amount'], 4, '.', '');

        if ($validated['operation'] === 'credit') {
            $transaction = $this->balanceService->credit(
                user: $targetUser,
                amount: $amount,
                type: 'topup',
                referenceType: 'recharge_admin',
                description: "Recharge by " . auth()->user()->name,
                createdBy: auth()->id(),
                source: 'recharge_admin',
                remarks: $validated['reason'],
            );

            // Create payment record
            Payment::create([
                'user_id' => $targetUser->id,
                'amount' => $amount,
                'currency' => 'USD',
                'payment_method' => 'recharge_admin',
                'recharged_by' => auth()->id(),
                'notes' => "Recharge by " . auth()->user()->name . ": " . $validated['reason'],
                'status' => 'completed',
                'completed_at' => now(),
                'transaction_id' => $transaction->id,
            ]);
        } else {
            $transaction = $this->balanceService->debit(
                user: $targetUser,
                amount: $amount,
                type: 'adjustment',
                referenceType: 'recharge_admin',
                description: "Adjustment by " . auth()->user()->name,
                createdBy: auth()->id(),
                source: 'recharge_admin',
                remarks: $validated['reason'],
            );
        }

        // MANDATORY audit logging with all required fields
        AuditService::logAction('balance.' . $validated['operation'], $targetUser, [
            'recharge_admin_id' => auth()->id(),
            'recharge_admin_name' => auth()->user()->name,
            'previous_balance' => $previousBalance,
            'amount' => $amount,
            'new_balance' => $targetUser->fresh()->balance,
            'reason' => $validated['reason'],
            'transaction_id' => $transaction->id,
        ]);

        $operationLabel = $validated['operation'] === 'credit' ? 'credited' : 'debited';

        return redirect()->route('recharge-admin.users.show', $targetUser)
            ->with('success', "Successfully {$operationLabel} \${$amount} " . ($validated['operation'] === 'credit' ? 'to' : 'from') . " {$targetUser->name}.");
    }
}
