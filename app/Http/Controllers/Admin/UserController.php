<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('parent', 'rateGroup');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $rateGroups = RateGroup::where('type', 'admin')->get();
        $resellers = User::where('role', 'reseller')->active()->get();

        return view('admin.users.create', compact('rateGroups', 'resellers'));
    }

    public function store(Request $request)
    {
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

        // Admin is parent for resellers, selected reseller is parent for clients
        if ($validated['role'] === 'reseller') {
            $validated['parent_id'] = auth()->id();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'parent_id' => $validated['parent_id'],
            'billing_type' => $validated['billing_type'],
            'rate_group_id' => $validated['rate_group_id'] ?? null,
            'balance' => $validated['balance'] ?? 0,
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'max_channels' => $validated['max_channels'] ?? 10,
            'status' => 'active',
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', ucfirst($validated['role']) . ' created successfully.');
    }

    public function show(User $user)
    {
        $user->load('parent', 'rateGroup', 'children', 'sipAccounts', 'kycProfile');

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $rateGroups = RateGroup::where('type', 'admin')->get();
        $resellers = User::where('role', 'reseller')->active()->get();

        return view('admin.users.edit', compact('user', 'rateGroups', 'resellers'));
    }

    public function update(Request $request, User $user)
    {
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

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();

        return back()->with('success', "User {$user->status}.");
    }
}
