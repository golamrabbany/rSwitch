<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\RateGroup;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('parent_id', auth()->id())
            ->where('role', 'client')
            ->with('rateGroup');

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

        $clients = $query->orderByDesc('created_at')->paginate(20);

        return view('reseller.clients.index', compact('clients'));
    }

    public function create()
    {
        $rateGroups = RateGroup::where('type', 'admin')->get();

        return view('reseller.clients.create', compact('rateGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'billing_type' => ['required', Rule::in(['prepaid', 'postpaid'])],
            'rate_group_id' => ['nullable', 'exists:rate_groups,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'max_channels' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'parent_id' => auth()->id(),
            'billing_type' => $validated['billing_type'],
            'rate_group_id' => $validated['rate_group_id'] ?? null,
            'balance' => 0,
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'max_channels' => $validated['max_channels'] ?? 10,
            'status' => 'active',
        ]);

        $user->assignRole('client');

        AuditService::logCreated($user, 'reseller.client.created');

        return redirect()->route('reseller.clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function show(User $client)
    {
        abort_unless(auth()->user()->canManage($client) && $client->role === 'client', 403);

        $client->load('rateGroup', 'sipAccounts', 'kycProfile');

        return view('reseller.clients.show', compact('client'));
    }

    public function edit(User $client)
    {
        abort_unless(auth()->user()->canManage($client) && $client->role === 'client', 403);

        $rateGroups = RateGroup::where('type', 'admin')->get();

        return view('reseller.clients.edit', compact('client', 'rateGroups'));
    }

    public function update(Request $request, User $client)
    {
        abort_unless(auth()->user()->canManage($client) && $client->role === 'client', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($client->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'billing_type' => ['required', Rule::in(['prepaid', 'postpaid'])],
            'rate_group_id' => ['nullable', 'exists:rate_groups,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'max_channels' => ['nullable', 'integer', 'min:1'],
        ]);

        $original = $client->getAttributes();

        $client->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'billing_type' => $validated['billing_type'],
            'rate_group_id' => $validated['rate_group_id'],
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'max_channels' => $validated['max_channels'] ?? 10,
        ]);

        if (! empty($validated['password'])) {
            $client->password = Hash::make($validated['password']);
        }

        $client->save();

        AuditService::logUpdated($client, $original, 'reseller.client.updated');

        return redirect()->route('reseller.clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function toggleStatus(User $client)
    {
        abort_unless(auth()->user()->canManage($client) && $client->role === 'client', 403);

        $original = $client->getAttributes();
        $client->status = $client->status === 'active' ? 'suspended' : 'active';
        $client->save();

        AuditService::logUpdated($client, $original, 'reseller.client.status_toggled');

        return back()->with('success', "Client {$client->status}.");
    }
}
