<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Did;
use App\Models\RingGroup;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DidController extends Controller
{
    public function index(Request $request)
    {
        $query = Did::with('trunk', 'assignedUser');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trunk_id')) {
            $query->where('trunk_id', $request->trunk_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%")
                  ->orWhereHas('assignedUser', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $dids = $query->orderByDesc('created_at')->paginate(20);

        $trunks = Trunk::whereIn('direction', ['incoming', 'both'])
            ->orderBy('name')
            ->get();

        return view('admin.dids.index', compact('dids', 'trunks'));
    }

    public function create(Request $request)
    {
        $trunks = Trunk::whereIn('direction', ['incoming', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $users = User::whereIn('role', ['reseller', 'client'])
            ->active()
            ->orderBy('name')
            ->get();

        $sipAccounts = SipAccount::where('status', 'active')
            ->with('user')
            ->orderBy('username')
            ->get();

        $ringGroups = RingGroup::where('status', 'active')->orderBy('name')->get();

        $selectedUserId = $request->query('user_id');

        return view('admin.dids.create', compact('trunks', 'users', 'sipAccounts', 'ringGroups', 'selectedUserId'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateDid($request);

        // Verify trunk direction
        $trunk = Trunk::findOrFail($validated['trunk_id']);
        if (!in_array($trunk->direction, ['incoming', 'both'])) {
            return back()->withErrors(['trunk_id' => 'Selected trunk must accept incoming calls.'])->withInput();
        }

        // Clear irrelevant destination fields
        $this->cleanDestinationFields($validated);

        $did = Did::create($validated);

        AuditService::logCreated($did, 'did.created');

        return redirect()->route('admin.dids.show', $did)
            ->with('success', "DID {$did->number} created.");
    }

    public function show(Did $did)
    {
        $did->load('trunk', 'assignedUser');

        $destinationSip = null;
        $destinationRingGroup = null;
        if ($did->destination_type === 'sip_account' && $did->destination_id) {
            $destinationSip = SipAccount::with('user')->find($did->destination_id);
        } elseif ($did->destination_type === 'ring_group' && $did->destination_id) {
            $destinationRingGroup = RingGroup::withCount('members')->find($did->destination_id);
        }

        return view('admin.dids.show', compact('did', 'destinationSip', 'destinationRingGroup'));
    }

    public function edit(Did $did)
    {
        $trunks = Trunk::whereIn('direction', ['incoming', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $users = User::whereIn('role', ['reseller', 'client'])
            ->active()
            ->orderBy('name')
            ->get();

        $sipAccounts = SipAccount::where('status', 'active')
            ->with('user')
            ->orderBy('username')
            ->get();

        $ringGroups = RingGroup::where('status', 'active')->orderBy('name')->get();

        return view('admin.dids.edit', compact('did', 'trunks', 'users', 'sipAccounts', 'ringGroups'));
    }

    public function update(Request $request, Did $did)
    {
        $validated = $this->validateDid($request, $did);
        $validated['status'] = $request->validate([
            'status' => ['required', Rule::in(['active', 'unassigned', 'disabled'])],
        ])['status'];

        // Verify trunk direction
        $trunk = Trunk::findOrFail($validated['trunk_id']);
        if (!in_array($trunk->direction, ['incoming', 'both'])) {
            return back()->withErrors(['trunk_id' => 'Selected trunk must accept incoming calls.'])->withInput();
        }

        $original = $did->getAttributes();

        // Clear irrelevant destination fields
        $this->cleanDestinationFields($validated);

        $did->update($validated);

        AuditService::logUpdated($did, $original, 'did.updated');

        return redirect()->route('admin.dids.show', $did)
            ->with('success', "DID {$did->number} updated.");
    }

    public function destroy(Did $did)
    {
        $number = $did->number;

        AuditService::logAction('did.deleted', $did, ['number' => $number]);

        $did->delete();

        return redirect()->route('admin.dids.index')
            ->with('success', "DID {$number} deleted.");
    }

    protected function validateDid(Request $request, ?Did $did = null): array
    {
        return $request->validate([
            'number' => [
                'required', 'string', 'max:20',
                'regex:/^\+?[1-9]\d{1,18}$/',
                $did ? Rule::unique('dids', 'number')->ignore($did->id) : 'unique:dids,number',
            ],
            'provider'            => ['required', 'string', 'max:100'],
            'trunk_id'            => ['required', 'exists:trunks,id'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'destination_type'    => ['required', Rule::in(['sip_account', 'ring_group', 'external'])],
            'destination_id'      => ['nullable', 'required_if:destination_type,sip_account', 'required_if:destination_type,ring_group'],
            'destination_number'  => ['nullable', 'required_if:destination_type,external', 'string', 'max:30'],
            'monthly_cost'        => ['required', 'numeric', 'min:0', 'max:9999.9999'],
            'monthly_price'       => ['required', 'numeric', 'min:0', 'max:9999.9999'],
        ]);
    }

    protected function cleanDestinationFields(array &$validated): void
    {
        match ($validated['destination_type']) {
            'sip_account' => $validated['destination_number'] = null,
            'ring_group'  => $validated['destination_number'] = null,
            'external'    => $validated['destination_id'] = null,
        };
    }
}
