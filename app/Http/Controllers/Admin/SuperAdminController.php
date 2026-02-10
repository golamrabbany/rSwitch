<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    /**
     * Display a listing of all super admin users.
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = User::where('role', 'super_admin');

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

        $superAdmins = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.super-admins.index', compact('superAdmins'));
    }

    /**
     * Show the form for creating a new super admin user.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('admin.super-admins.create');
    }

    /**
     * Store a newly created super admin user.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
        ]);

        $superAdmin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'super_admin',
            'status' => $validated['status'],
            'billing_type' => 'postpaid',
            'balance' => 0,
        ]);

        $superAdmin->assignRole('super_admin');

        AuditService::logCreated($superAdmin, 'super_admin.created');

        return redirect()->route('admin.super-admins.index')
            ->with('success', 'Super Admin created successfully.');
    }

    /**
     * Display the specified super admin user.
     */
    public function show(User $superAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($superAdmin->isSuperAdmin(), 404);

        return view('admin.super-admins.show', compact('superAdmin'));
    }

    /**
     * Show the form for editing the specified super admin user.
     */
    public function edit(User $superAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($superAdmin->isSuperAdmin(), 404);

        return view('admin.super-admins.edit', compact('superAdmin'));
    }

    /**
     * Update the specified super admin user.
     */
    public function update(Request $request, User $superAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($superAdmin->isSuperAdmin(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($superAdmin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
        ]);

        $oldValues = $superAdmin->only(['name', 'email', 'status']);

        $superAdmin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $superAdmin->update(['password' => Hash::make($validated['password'])]);
        }

        AuditService::logUpdated($superAdmin, 'super_admin.updated', [
            'old_values' => $oldValues,
            'new_values' => $superAdmin->only(['name', 'email', 'status']),
        ]);

        return redirect()->route('admin.super-admins.index')
            ->with('success', 'Super Admin updated successfully.');
    }

    /**
     * Remove the specified super admin user.
     */
    public function destroy(User $superAdmin)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($superAdmin->isSuperAdmin(), 404);

        // Cannot delete self
        if ($superAdmin->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last super admin
        $superAdminCount = User::where('role', 'super_admin')->count();
        if ($superAdminCount <= 1) {
            return back()->with('error', 'Cannot delete the last super admin account.');
        }

        AuditService::logDeleted($superAdmin, 'super_admin.deleted');

        $superAdmin->delete();

        return redirect()->route('admin.super-admins.index')
            ->with('success', 'Super Admin deleted successfully.');
    }
}
