<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RechargeAdminController extends Controller
{
    public function __construct()
    {
        // Only super admin can manage recharge admins
    }

    /**
     * Display a listing of all recharge admin users.
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = User::where('role', 'recharge_admin')
            ->with('assignedResellers')
            ->withCount('assignedResellers');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rechargeAdmins = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.recharge-admins.index', compact('rechargeAdmins'));
    }

    /**
     * Show the form for creating a new recharge admin user.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $resellers = User::where('role', 'reseller')->orderBy('name')->get();

        return view('admin.recharge-admins.create', compact('resellers'));
    }

    /**
     * Store a newly created recharge admin user.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
            'reseller_ids' => ['required', 'array', 'min:1'],
            'reseller_ids.*' => ['exists:users,id'],
        ], [
            'reseller_ids.required' => 'At least one reseller must be assigned.',
            'reseller_ids.min' => 'At least one reseller must be assigned.',
        ]);

        $rechargeAdmin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'recharge_admin',
            'status' => $validated['status'],
            'billing_type' => 'postpaid',
            'balance' => 0,
        ]);

        $rechargeAdmin->assignRole('recharge_admin');

        // Assign resellers (mandatory)
        $rechargeAdmin->assignedResellers()->sync($validated['reseller_ids']);

        AuditService::logCreated($rechargeAdmin, 'recharge_admin.created');

        return redirect()->route('admin.recharge-admins.index')
            ->with('success', 'Recharge Admin created successfully.');
    }

    /**
     * Display the specified recharge admin user.
     */
    public function show(User $rechargeAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($rechargeAdmin->isRechargeAdmin(), 404);

        $rechargeAdmin->load(['assignedResellers' => function ($query) {
            $query->withCount('children');
        }]);

        // Get stats for this recharge admin's scope
        $resellerIds = $rechargeAdmin->assignedResellers()->pluck('users.id')->toArray();
        $clientIds = User::whereIn('parent_id', $resellerIds)->pluck('id')->toArray();

        $stats = [
            'resellers' => count($resellerIds),
            'clients' => count($clientIds),
            'total_transactions' => \App\Models\Transaction::where('created_by', $rechargeAdmin->id)->count(),
            'transactions_today' => \App\Models\Transaction::where('created_by', $rechargeAdmin->id)
                ->whereDate('created_at', today())
                ->count(),
        ];

        // Recent transactions by this recharge admin
        $recentTransactions = \App\Models\Transaction::where('created_by', $rechargeAdmin->id)
            ->with('user:id,name,role')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.recharge-admins.show', compact('rechargeAdmin', 'stats', 'recentTransactions'));
    }

    /**
     * Show the form for editing the specified recharge admin user.
     */
    public function edit(User $rechargeAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($rechargeAdmin->isRechargeAdmin(), 404);

        $resellers = User::where('role', 'reseller')->orderBy('name')->get();
        $assignedIds = $rechargeAdmin->assignedResellers()->pluck('users.id')->toArray();

        return view('admin.recharge-admins.edit', compact('rechargeAdmin', 'resellers', 'assignedIds'));
    }

    /**
     * Update the specified recharge admin user.
     */
    public function update(Request $request, User $rechargeAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($rechargeAdmin->isRechargeAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($rechargeAdmin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
            'reseller_ids' => ['required', 'array', 'min:1'],
            'reseller_ids.*' => ['exists:users,id'],
        ], [
            'reseller_ids.required' => 'At least one reseller must be assigned.',
            'reseller_ids.min' => 'At least one reseller must be assigned.',
        ]);

        $oldValues = $rechargeAdmin->only(['name', 'email', 'status']);
        $oldResellers = $rechargeAdmin->assignedResellers()->pluck('users.id')->toArray();

        $rechargeAdmin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $rechargeAdmin->update(['password' => Hash::make($validated['password'])]);
        }

        // Sync reseller assignments
        $rechargeAdmin->assignedResellers()->sync($validated['reseller_ids']);

        AuditService::logAction('recharge_admin.updated', $rechargeAdmin, [
            'old_values' => array_merge($oldValues, ['assigned_resellers' => $oldResellers]),
            'new_values' => array_merge(
                $rechargeAdmin->only(['name', 'email', 'status']),
                ['assigned_resellers' => $validated['reseller_ids']]
            ),
        ]);

        return redirect()->route('admin.recharge-admins.index')
            ->with('success', 'Recharge Admin updated successfully.');
    }

    /**
     * Remove the specified recharge admin user.
     */
    public function destroy(User $rechargeAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($rechargeAdmin->isRechargeAdmin(), 404);

        AuditService::logAction('recharge_admin.deleted', $rechargeAdmin, [
            'name' => $rechargeAdmin->name,
            'email' => $rechargeAdmin->email,
        ]);

        // Detach all reseller assignments
        $rechargeAdmin->assignedResellers()->detach();

        $rechargeAdmin->delete();

        return redirect()->route('admin.recharge-admins.index')
            ->with('success', 'Recharge Admin deleted successfully.');
    }
}
