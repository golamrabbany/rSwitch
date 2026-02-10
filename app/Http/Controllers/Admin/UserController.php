<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RateGroup;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = auth()->user();
        $query = User::with('parent', 'rateGroup')
            ->withCount(['children', 'sipAccounts']);

        // Apply scoping for non-super admins
        if (!$authUser->isSuperAdmin()) {
            $query->visibleTo($authUser);
        }

        // Don't show super_admin or admin users in this list (they're managed in Admin Management)
        $query->whereNotIn('role', ['super_admin', 'admin']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get resellers for the filter dropdown (scoped for non-super admins)
        if ($request->role === 'client') {
            $resellerQuery = User::where('role', 'reseller')->orderBy('name');
            if (!$authUser->isSuperAdmin()) {
                $resellerQuery->whereIn('id', $authUser->managedResellerIds());
            }
            $resellers = $resellerQuery->get(['id', 'name', 'email']);
        } else {
            $resellers = collect();
        }

        return view('admin.users.index', compact('users', 'resellers'));
    }

    public function create()
    {
        $authUser = auth()->user();
        $rateGroups = RateGroup::where('type', 'admin')->get();

        // Scope resellers for non-super admins
        $resellerQuery = User::where('role', 'reseller')->active();
        if (!$authUser->isSuperAdmin()) {
            $resellerQuery->whereIn('id', $authUser->managedResellerIds());
        }
        $resellers = $resellerQuery->get();

        return view('admin.users.create', compact('rateGroups', 'resellers'));
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['reseller', 'client'])],
            'parent_id' => ['nullable', 'exists:users,id'],
            'billing_type' => ['required', Rule::in(['prepaid', 'postpaid'])],
            'rate_group_id' => ['nullable', 'exists:rate_groups,id'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'max_channels' => ['nullable', 'integer', 'min:1'],
        ]);

        // For non-super admins: validate they can create resellers/clients
        if (!$authUser->isSuperAdmin()) {
            // Regular admins cannot create new resellers (they can only manage assigned ones)
            if ($validated['role'] === 'reseller') {
                abort(403, 'You can only manage existing assigned resellers.');
            }

            // For clients, validate parent_id is within their assigned resellers
            if ($validated['role'] === 'client' && !empty($validated['parent_id'])) {
                $managedResellerIds = $authUser->managedResellerIds();
                if (!in_array($validated['parent_id'], $managedResellerIds)) {
                    abort(403, 'You can only create clients under your assigned resellers.');
                }
            }
        }

        // Super Admin is parent for resellers, selected reseller is parent for clients
        if ($validated['role'] === 'reseller') {
            $validated['parent_id'] = auth()->id();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'parent_id' => $validated['parent_id'] ?? null,
            'billing_type' => $validated['billing_type'],
            'rate_group_id' => $validated['rate_group_id'] ?? null,
            'balance' => $validated['balance'] ?? 0,
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'max_channels' => $validated['max_channels'] ?? 10,
            'status' => 'active',
        ]);

        $user->assignRole($validated['role']);

        AuditService::logCreated($user, 'user.created');

        return redirect()->route('admin.users.index', ['role' => $validated['role']])
            ->with('success', ucfirst($validated['role']) . ' created successfully.');
    }

    public function show(User $user)
    {
        // Check authorization for non-super admins
        abort_unless(auth()->user()->canManage($user), 403);

        $user->load('parent', 'rateGroup', 'children', 'sipAccounts', 'kycProfile', 'dids');

        // Get last 15 transactions for balance history
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('admin.users.show', compact('user', 'recentTransactions'));
    }

    /**
     * Adjust user balance via AJAX from modal.
     */
    public function adjustBalance(Request $request, User $user, BalanceService $balanceService)
    {
        abort_unless(auth()->user()->canManage($user), 403);

        $validated = $request->validate([
            'operation' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'source' => ['required', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = number_format((float) $validated['amount'], 4, '.', '');
        $source = $validated['source'];
        $remarks = $validated['remarks'] ?? '';

        try {
            if ($validated['operation'] === 'credit') {
                $transaction = $balanceService->credit(
                    user: $user,
                    amount: $amount,
                    type: 'topup',
                    referenceType: 'manual_admin',
                    description: "Admin topup by " . auth()->user()->name,
                    createdBy: auth()->id(),
                    source: $source,
                    remarks: $remarks,
                );

                Payment::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => 'USD',
                    'payment_method' => $source,
                    'recharged_by' => auth()->id(),
                    'notes' => $remarks ?: 'Admin manual topup',
                    'status' => 'completed',
                    'completed_at' => now(),
                    'transaction_id' => $transaction->id,
                ]);

                AuditService::logAction('balance.credit', $user, [
                    'amount' => $amount,
                    'source' => $source,
                    'remarks' => $remarks,
                    'transaction_id' => $transaction->id,
                ]);

                return back()->with('success', "Credited \${$amount} to {$user->name}. New balance: \$" . number_format($user->fresh()->balance, 2));
            }

            $transaction = $balanceService->debit(
                user: $user,
                amount: $amount,
                type: 'adjustment',
                referenceType: 'manual_admin',
                description: "Admin debit by " . auth()->user()->name,
                createdBy: auth()->id(),
                source: $source,
                remarks: $remarks,
            );

            AuditService::logAction('balance.debit', $user, [
                'amount' => $amount,
                'source' => $source,
                'remarks' => $remarks,
                'transaction_id' => $transaction->id,
            ]);

            return back()->with('success', "Debited \${$amount} from {$user->name}. New balance: \$" . number_format($user->fresh()->balance, 2));

        } catch (\App\Exceptions\Billing\InsufficientBalanceException $e) {
            return back()->with('error', "Insufficient balance. Available: \$" . number_format($e->available, 2));
        }
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->canManage($user), 403);

        $authUser = auth()->user();
        $rateGroups = RateGroup::where('type', 'admin')->get();

        // Scope resellers for non-super admins
        $resellerQuery = User::where('role', 'reseller')->active();
        if (!$authUser->isSuperAdmin()) {
            $resellerQuery->whereIn('id', $authUser->managedResellerIds());
        }
        $resellers = $resellerQuery->get();

        return view('admin.users.edit', compact('user', 'rateGroups', 'resellers'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->canManage($user), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
            'billing_type' => ['required', Rule::in(['prepaid', 'postpaid'])],
            'rate_group_id' => ['nullable', 'exists:rate_groups,id'],
            'balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'max_channels' => ['nullable', 'integer', 'min:1'],
            'daily_spend_limit' => ['nullable', 'numeric', 'min:0'],
            'daily_call_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $original = $user->getAttributes();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'billing_type' => $validated['billing_type'],
            'rate_group_id' => $validated['rate_group_id'],
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'max_channels' => $validated['max_channels'] ?? 10,
            'daily_spend_limit' => $validated['daily_spend_limit'],
            'daily_call_limit' => $validated['daily_call_limit'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if (isset($validated['balance'])) {
            $user->balance = $validated['balance'];
        }

        $user->save();

        AuditService::logUpdated($user, $original, 'user.updated');

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        abort_unless(auth()->user()->canManage($user), 403);

        $original = $user->getAttributes();
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();

        AuditService::logUpdated($user, $original, 'user.status_toggled');

        return back()->with('success', "User {$user->status}.");
    }
}
