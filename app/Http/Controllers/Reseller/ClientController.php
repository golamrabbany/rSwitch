<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\KycProfile;
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
            ->with('rateGroup')
            ->withCount('sipAccounts');

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
        $user = auth()->user();

        // Show base tariff (admin-assigned) + reseller's own tariffs
        $rateGroups = RateGroup::where(function ($q) use ($user) {
            $q->where('id', $user->rate_group_id) // Base tariff
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('created_by', $user->id)->where('type', 'reseller');
              });
        })->get();

        $channelInfo = $this->getChannelInfo();

        return view('reseller.clients.create', compact('rateGroups', 'channelInfo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Account
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'billing_type' => ['required', Rule::in(['prepaid', 'postpaid'])],
            'rate_group_id' => ['nullable', 'exists:rate_groups,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'max_channels' => ['nullable', 'integer', 'min:1'],
            // KYC
            'account_type' => ['required', Rule::in(['individual', 'company'])],
            'kyc_full_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'id_type' => ['required', Rule::in(['national_id', 'passport', 'driving_license', 'business_license'])],
            'id_number' => ['required', 'string', 'max:50'],
            'id_expiry_date' => ['nullable', 'date'],
            // Contact
            'contact_email' => ['nullable', 'email', 'max:255'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'user_city' => ['nullable', 'string', 'max:100'],
            'user_state' => ['nullable', 'string', 'max:100'],
            'user_country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            // Company
            'company_name' => ['nullable', 'string', 'max:200'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Documents
            'id_front' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
            'id_back' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        // Validate max_channels against reseller's pool
        $channelInfo = $this->getChannelInfo();
        $requestedChannels = (int) ($validated['max_channels'] ?? 10);
        if ($requestedChannels > $channelInfo['available']) {
            return back()->withErrors([
                'max_channels' => "Exceeds available channels. You have {$channelInfo['available']} of {$channelInfo['total']} channels available.",
            ])->withInput();
        }

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
            'kyc_status' => 'pending',
            'phone' => $validated['phone'],
            'alt_phone' => $validated['alt_phone'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['user_city'] ?? null,
            'state' => $validated['user_state'] ?? null,
            'country' => $validated['user_country'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'company_email' => $validated['company_email'] ?? null,
            'company_website' => $validated['company_website'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $user->assignRole('client');

        // Create KYC Profile
        $kycProfile = KycProfile::create([
            'user_id' => $user->id,
            'account_type' => $validated['account_type'],
            'full_name' => $validated['kyc_full_name'],
            'contact_person' => $validated['contact_person'],
            'phone' => $validated['phone'],
            'address_line1' => $validated['address_line1'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'country' => strtoupper($validated['country']),
            'id_type' => $validated['id_type'],
            'id_number' => $validated['id_number'],
            'id_expiry_date' => $validated['id_expiry_date'],
            'submitted_at' => now(),
        ]);

        // Upload KYC documents
        foreach (['id_front', 'id_back'] as $docType) {
            if ($request->hasFile($docType)) {
                $path = $request->file($docType)->store("kyc/{$user->id}", 'local');
                KycDocument::create([
                    'kyc_profile_id' => $kycProfile->id,
                    'document_type' => $docType,
                    'file_path' => $path,
                    'original_name' => $request->file($docType)->getClientOriginalName(),
                    'mime_type' => $request->file($docType)->getMimeType(),
                    'file_size' => $request->file($docType)->getSize(),
                ]);
            }
        }

        AuditService::logCreated($user, 'reseller.client.created');

        return redirect()->route('reseller.clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function show(User $client)
    {
        abort_unless(auth()->user()->canManage($client) && $client->role === 'client', 403);

        $client->load('rateGroup', 'sipAccounts', 'kycProfile');

        $recentTopups = \App\Models\Transaction::where('user_id', $client->id)
            ->where('type', 'topup')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // SIP channel pool info
        $sipChannelUsed = $client->sipAccounts->sum('max_channels');
        $sipChannelAvailable = max(0, $client->max_channels - $sipChannelUsed);

        return view('reseller.clients.show', compact('client', 'recentTopups', 'sipChannelAvailable'));
    }

    public function edit(User $client)
    {
        abort_unless(auth()->user()->canManage($client) && $client->role === 'client', 403);

        $user = auth()->user();

        // Show base tariff + reseller's own tariffs
        $rateGroups = RateGroup::where(function ($q) use ($user) {
            $q->where('id', $user->rate_group_id)
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('created_by', $user->id)->where('type', 'reseller');
              });
        })->get();

        $channelInfo = $this->getChannelInfo($client->id);

        return view('reseller.clients.edit', compact('client', 'rateGroups', 'channelInfo'));
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
            'phone' => ['nullable', 'string', 'max:20'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Validate max_channels against reseller's pool (exclude current client)
        $channelInfo = $this->getChannelInfo($client->id);
        $requestedChannels = (int) ($validated['max_channels'] ?? 10);
        if ($requestedChannels > $channelInfo['available']) {
            return back()->withErrors([
                'max_channels' => "Exceeds available channels. You have {$channelInfo['available']} of {$channelInfo['total']} channels available.",
            ])->withInput();
        }

        $original = $client->getAttributes();

        $client->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'],
            'billing_type' => $validated['billing_type'],
            'rate_group_id' => $validated['rate_group_id'],
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'max_channels' => $validated['max_channels'] ?? 10,
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

    /**
     * Get available channels from reseller's pool.
     */
    private function getChannelInfo(?int $excludeClientId = null): array
    {
        $reseller = auth()->user();
        $totalChannels = $reseller->max_channels;

        $usedQuery = User::where('parent_id', $reseller->id)->where('role', 'client');
        if ($excludeClientId) {
            $usedQuery->where('id', '!=', $excludeClientId);
        }
        $usedChannels = $usedQuery->sum('max_channels');

        return [
            'total' => $totalChannels,
            'used' => (int) $usedChannels,
            'available' => max(0, $totalChannels - $usedChannels),
        ];
    }
}
