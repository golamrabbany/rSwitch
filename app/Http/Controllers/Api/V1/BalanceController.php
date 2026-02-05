<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function __construct(
        private BalanceService $balanceService,
    ) {}

    /**
     * GET /api/v1/balance — Check current balance.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'balance' => $user->balance,
            'currency' => $user->currency,
            'billing_type' => $user->billing_type,
            'credit_limit' => $user->credit_limit,
            'available' => $this->balanceService->getAvailableBalance($user),
        ]);
    }

    /**
     * POST /api/v1/balance/topup — Add funds to a user (admin/reseller only).
     */
    public function topup(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->isAdmin() && !$authUser->isReseller()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        // Resellers can only topup their own clients
        if ($authUser->isReseller()) {
            if ($targetUser->parent_id !== $authUser->id || $targetUser->role !== 'client') {
                return response()->json(['message' => 'You can only topup your own clients.'], 403);
            }
        }

        $amount = number_format((float) $validated['amount'], 4, '.', '');
        $notes = $validated['notes'] ?? '';
        $paymentMethod = $authUser->isAdmin() ? 'manual_admin' : 'manual_reseller';

        $transaction = $this->balanceService->credit(
            user: $targetUser,
            amount: $amount,
            type: 'topup',
            referenceType: $paymentMethod,
            description: $notes ?: "API topup by {$authUser->name}",
            createdBy: $authUser->id,
        );

        Payment::create([
            'user_id' => $targetUser->id,
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => $paymentMethod,
            'recharged_by' => $authUser->id,
            'notes' => $notes ?: 'API topup',
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_id' => $transaction->id,
        ]);

        AuditService::logAction('balance.credit', $targetUser, [
            'amount' => $amount,
            'notes' => $notes,
            'via' => 'api',
            'by' => $authUser->id,
            'transaction_id' => $transaction->id,
        ]);

        return response()->json([
            'message' => "Credited \${$amount} to {$targetUser->name}.",
            'transaction_id' => $transaction->id,
            'new_balance' => $targetUser->fresh()->balance,
        ]);
    }
}
