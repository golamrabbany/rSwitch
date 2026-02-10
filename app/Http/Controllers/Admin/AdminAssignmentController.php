<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminAssignmentController extends Controller
{
    /**
     * Display a listing of all admin users.
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = User::where('role', 'admin')
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

        $admins = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.admins.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin user.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $resellers = User::where('role', 'reseller')->orderBy('name')->get();

        return view('admin.admins.create', compact('resellers'));
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
            'reseller_ids' => ['array'],
            'reseller_ids.*' => ['exists:users,id'],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'status' => $validated['status'],
            'billing_type' => 'postpaid',
            'balance' => 0,
        ]);

        $admin->assignRole('admin');

        // Assign resellers
        if (!empty($validated['reseller_ids'])) {
            $admin->assignedResellers()->sync($validated['reseller_ids']);
        }

        AuditService::logCreated($admin, 'admin.created');

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin user created successfully.');
    }

    /**
     * Display the specified admin user.
     */
    public function show(User $admin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($admin->isRegularAdmin(), 404);

        $admin->load(['assignedResellers' => function ($query) {
            $query->withCount('children');
        }]);

        // Get stats for this admin's scope
        $stats = [
            'resellers' => $admin->assignedResellers()->count(),
            'clients' => User::whereIn('parent_id', $admin->assignedResellers()->pluck('users.id'))->count(),
            'sip_accounts' => \App\Models\SipAccount::whereIn('user_id', $admin->descendantIds())->count(),
        ];

        return view('admin.admins.show', compact('admin', 'stats'));
    }

    /**
     * Show the form for editing the specified admin user.
     */
    public function edit(User $admin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($admin->isRegularAdmin(), 404);

        $resellers = User::where('role', 'reseller')->orderBy('name')->get();
        $assignedIds = $admin->assignedResellers()->pluck('users.id')->toArray();

        return view('admin.admins.edit', compact('admin', 'resellers', 'assignedIds'));
    }

    /**
     * Update the specified admin user.
     */
    public function update(Request $request, User $admin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($admin->isRegularAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
            'reseller_ids' => ['array'],
            'reseller_ids.*' => ['exists:users,id'],
        ]);

        $oldValues = $admin->only(['name', 'email', 'status']);
        $oldResellers = $admin->assignedResellers()->pluck('users.id')->toArray();

        $admin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $admin->update(['password' => Hash::make($validated['password'])]);
        }

        // Sync reseller assignments
        $admin->assignedResellers()->sync($validated['reseller_ids'] ?? []);

        $newResellers = $validated['reseller_ids'] ?? [];

        AuditService::logUpdated($admin, 'admin.updated', [
            'old_values' => array_merge($oldValues, ['assigned_resellers' => $oldResellers]),
            'new_values' => array_merge(
                $admin->only(['name', 'email', 'status']),
                ['assigned_resellers' => $newResellers]
            ),
        ]);

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin user updated successfully.');
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy(User $admin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($admin->isRegularAdmin(), 404);

        // Cannot delete self
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        AuditService::logDeleted($admin, 'admin.deleted');

        // Detach all reseller assignments
        $admin->assignedResellers()->detach();

        $admin->delete();

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin user deleted successfully.');
    }
}
