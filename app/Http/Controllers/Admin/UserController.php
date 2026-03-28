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
        $query = User::with('parent', 'rateGroup', 'kycProfile.reviewer')
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
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('billing_type')) {
            $query->where('billing_type', $request->billing_type);
        }

        if ($request->filled('rate_group_id')) {
            $query->where('rate_group_id', $request->rate_group_id);
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        // KYC stats for client list
        $kycStats = [];
        if ($request->role === 'client') {
            $kycBase = User::where('role', 'client');
            if (!$authUser->isSuperAdmin()) {
                $kycBase->visibleTo($authUser);
            }
            $kycStats = [
                'total' => (clone $kycBase)->count(),
                'pending' => (clone $kycBase)->where('kyc_status', 'pending')->count(),
                'approved' => (clone $kycBase)->where('kyc_status', 'approved')->count(),
                'rejected' => (clone $kycBase)->where('kyc_status', 'rejected')->count(),
            ];
        }

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

        $rateGroups = RateGroup::where('type', 'admin')->orderBy('name')->get(['id', 'name']);

        // Build KYC data for modal (client list only)
        $kycDataJson = [];
        if ($request->role === 'client') {
            foreach ($users as $u) {
                if ($u->kycProfile) {
                    $kyc = $u->kycProfile;
                    $addressParts = array_filter([
                        $kyc->address_line1,
                        $kyc->address_line2,
                        implode(', ', array_filter([$kyc->city, $kyc->state])),
                        implode(' ', array_filter([$kyc->country, $kyc->postal_code])),
                    ]);
                    $kycDataJson[$u->id] = [
                        'name' => $u->name,
                        'kyc_status' => $u->kyc_status,
                        'kyc_profile_id' => $kyc->id,
                        'account_type' => ucfirst($kyc->account_type ?? ''),
                        'full_name' => $kyc->full_name,
                        'contact_person' => $kyc->contact_person,
                        'phone' => $kyc->phone,
                        'alt_phone' => $kyc->alt_phone,
                        'id_type' => $kyc->id_type ? ucfirst(str_replace('_', ' ', $kyc->id_type)) : null,
                        'id_number' => $kyc->id_number,
                        'id_expiry' => $kyc->id_expiry_date?->format('d M Y'),
                        'submitted_at' => $kyc->submitted_at?->format('d M Y, g:i A'),
                        'reviewed_at' => $kyc->reviewed_at?->format('d M Y'),
                        'reviewer' => $kyc->reviewer?->name,
                        'address' => implode(', ', $addressParts),
                        'rejected_reason' => $u->kyc_rejected_reason,
                    ];
                }
            }
        }

        return view('admin.users.index', compact('users', 'resellers', 'rateGroups', 'kycDataJson', 'kycStats'));
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
            'phone' => ['nullable', 'string', 'max:20'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // For non-super admins (Regular Admin): enforce scoping rules
        if (!$authUser->isSuperAdmin()) {
            // For clients: parent_id (reseller) is REQUIRED and must be within assigned resellers
            if ($validated['role'] === 'client') {
                if (empty($validated['parent_id'])) {
                    return back()->withErrors(['parent_id' => 'You must select a parent reseller for the client.'])->withInput();
                }

                $managedResellerIds = $authUser->managedResellerIds();
                if (!in_array($validated['parent_id'], $managedResellerIds)) {
                    abort(403, 'You can only create clients under your assigned resellers.');
                }
            }
        }

        // Super Admin is parent for resellers, selected reseller is parent for clients
        if ($validated['role'] === 'reseller') {
            $validated['parent_id'] = null; // Resellers have no parent
        } elseif ($validated['role'] === 'client' && empty($validated['parent_id'])) {
            // Direct client — parent is the Super Admin
            $validated['parent_id'] = $authUser->isSuperAdmin() ? $authUser->id : null;
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
            'sip_ranges' => $this->parseSipRanges($request->input('sip_ranges')),
            'status' => 'active',
            'phone' => $validated['phone'] ?? null,
            'alt_phone' => $validated['alt_phone'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'company_email' => $validated['company_email'] ?? null,
            'company_website' => $validated['company_website'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        // Auto-assign newly created reseller to the Regular Admin who created them
        if ($validated['role'] === 'reseller' && $authUser->isRegularAdmin()) {
            $authUser->assignedResellers()->attach($user->id);
            $authUser->clearHierarchyCache(); // Clear cache so new reseller is visible immediately
        }

        AuditService::logCreated($user, 'user.created');

        return redirect()->route('admin.users.index', ['role' => $validated['role']])
            ->with('success', ucfirst($validated['role']) . ' created successfully.');
    }

    public function show(User $user)
    {
        // Check authorization for non-super admins
        abort_unless(auth()->user()->canManage($user), 403);

        $user->load('parent', 'rateGroup', 'children', 'sipAccounts', 'kycProfile.documents', 'kycProfile.reviewer', 'dids');

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
            'adjust_reseller' => ['nullable', 'boolean'],
        ]);

        $amount = number_format((float) $validated['amount'], 4, '.', '');
        $source = $validated['source'];
        $remarks = $validated['remarks'] ?? '';
        $adjustReseller = (bool) ($validated['adjust_reseller'] ?? false);

        // Find parent reseller if checkbox was checked
        $reseller = null;
        if ($adjustReseller && $user->parent_id) {
            $reseller = User::find($user->parent_id);
            if (!$reseller || !$reseller->isReseller()) {
                $reseller = null;
            }
        }

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

                // Also credit parent reseller
                $resellerTxn = null;
                if ($reseller) {
                    $resellerTxn = $balanceService->credit(
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
                    'payment_method' => $source,
                    'recharged_by' => auth()->id(),
                    'notes' => $remarks ?: 'Admin manual topup',
                    'status' => 'completed',
                    'completed_at' => now(),
                    'transaction_id' => $transaction->id,
                    'reseller_transaction_id' => $resellerTxn?->id,
                ]);

                AuditService::logAction('balance.credit', $user, [
                    'amount' => $amount,
                    'source' => $source,
                    'remarks' => $remarks,
                    'transaction_id' => $transaction->id,
                    'reseller_credited' => $reseller ? true : false,
                ]);

                $msg = "Credited \${$amount} to {$user->name}.";
                if ($reseller) {
                    $msg .= " Also credited to reseller {$reseller->name}.";
                }
                return back()->with('success', $msg . " New balance: \$" . number_format($user->fresh()->balance, 2));
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

            // Also debit parent reseller
            if ($reseller) {
                $balanceService->debit(
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
                'source' => $source,
                'remarks' => $remarks,
                'transaction_id' => $transaction->id,
                'reseller_debited' => $reseller ? true : false,
            ]);

            $msg = "Debited \${$amount} from {$user->name}.";
            if ($reseller) {
                $msg .= " Also debited from reseller {$reseller->name}.";
            }
            return back()->with('success', $msg . " New balance: \$" . number_format($user->fresh()->balance, 2));

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
            'phone' => ['nullable', 'string', 'max:20'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'sip_ranges' => $this->parseSipRanges($request->input('sip_ranges')),
            'daily_spend_limit' => $validated['daily_spend_limit'],
            'daily_call_limit' => $validated['daily_call_limit'],
            'phone' => $validated['phone'],
            'alt_phone' => $validated['alt_phone'],
            'contact_email' => $validated['contact_email'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'zip_code' => $validated['zip_code'],
            'company_name' => $validated['company_name'],
            'company_email' => $validated['company_email'],
            'company_website' => $validated['company_website'],
            'notes' => $validated['notes'],
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

    /**
     * Parse SIP ranges from form input, filter empty and validate.
     */
    private function parseSipRanges(?array $ranges): ?array
    {
        if (!$ranges) return null;

        $valid = [];
        foreach ($ranges as $range) {
            $start = trim($range['start'] ?? '');
            $end = trim($range['end'] ?? '');
            if ($start !== '' && $end !== '' && ctype_digit($start) && ctype_digit($end)) {
                if (strcmp($start, $end) <= 0) {
                    $valid[] = ['start' => $start, 'end' => $end];
                }
            }
        }

        return !empty($valid) ? $valid : null;
    }
}
