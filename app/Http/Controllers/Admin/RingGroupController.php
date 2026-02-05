<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RingGroup;
use App\Models\SipAccount;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RingGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = RingGroup::with('user:id,name')
            ->withCount('members');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $ringGroups = $query->orderBy('name')->paginate(20);

        return view('admin.ring-groups.index', compact('ringGroups'));
    }

    public function create()
    {
        $sipAccounts = SipAccount::where('status', 'active')
            ->with('user:id,name')
            ->orderBy('username')
            ->get();

        $users = User::whereIn('role', ['reseller', 'client'])
            ->active()
            ->orderBy('name')
            ->get();

        return view('admin.ring-groups.create', compact('sipAccounts', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'strategy'     => ['required', Rule::in(['simultaneous', 'sequential', 'random'])],
            'ring_timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'user_id'      => ['nullable', 'exists:users,id'],
            'members'      => ['required', 'array', 'min:1'],
            'members.*.sip_account_id' => ['required', 'exists:sip_accounts,id'],
            'members.*.priority'       => ['required', 'integer', 'min:1', 'max:100'],
            'members.*.delay'          => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        $ringGroup = RingGroup::create([
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'strategy'     => $validated['strategy'],
            'ring_timeout' => $validated['ring_timeout'],
            'user_id'      => $validated['user_id'] ?? null,
        ]);

        $this->syncMembers($ringGroup, $validated['members']);

        AuditService::logCreated($ringGroup, 'ring_group.created');

        return redirect()->route('admin.ring-groups.show', $ringGroup)
            ->with('success', "Ring group \"{$ringGroup->name}\" created.");
    }

    public function show(RingGroup $ringGroup)
    {
        $ringGroup->load('members.user:id,name', 'user:id,name');

        $dids = $ringGroup->dids();

        return view('admin.ring-groups.show', compact('ringGroup', 'dids'));
    }

    public function edit(RingGroup $ringGroup)
    {
        $ringGroup->load('members');

        $sipAccounts = SipAccount::where('status', 'active')
            ->with('user:id,name')
            ->orderBy('username')
            ->get();

        $users = User::whereIn('role', ['reseller', 'client'])
            ->active()
            ->orderBy('name')
            ->get();

        return view('admin.ring-groups.edit', compact('ringGroup', 'sipAccounts', 'users'));
    }

    public function update(Request $request, RingGroup $ringGroup)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'strategy'     => ['required', Rule::in(['simultaneous', 'sequential', 'random'])],
            'ring_timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'user_id'      => ['nullable', 'exists:users,id'],
            'status'       => ['required', Rule::in(['active', 'disabled'])],
            'members'      => ['required', 'array', 'min:1'],
            'members.*.sip_account_id' => ['required', 'exists:sip_accounts,id'],
            'members.*.priority'       => ['required', 'integer', 'min:1', 'max:100'],
            'members.*.delay'          => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        $original = $ringGroup->getAttributes();

        $ringGroup->update([
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'strategy'     => $validated['strategy'],
            'ring_timeout' => $validated['ring_timeout'],
            'user_id'      => $validated['user_id'] ?? null,
            'status'       => $validated['status'],
        ]);

        $this->syncMembers($ringGroup, $validated['members']);

        AuditService::logUpdated($ringGroup, $original, 'ring_group.updated');

        return redirect()->route('admin.ring-groups.show', $ringGroup)
            ->with('success', "Ring group \"{$ringGroup->name}\" updated.");
    }

    public function destroy(RingGroup $ringGroup)
    {
        // Check if any DIDs reference this ring group
        $didCount = \App\Models\Did::where('destination_type', 'ring_group')
            ->where('destination_id', $ringGroup->id)
            ->count();

        if ($didCount > 0) {
            return back()->with('warning', "Cannot delete: {$didCount} DID(s) still use this ring group as destination.");
        }

        $name = $ringGroup->name;

        AuditService::logAction('ring_group.deleted', $ringGroup, $ringGroup->toArray());

        $ringGroup->delete();

        return redirect()->route('admin.ring-groups.index')
            ->with('success', "Ring group \"{$name}\" deleted.");
    }

    private function syncMembers(RingGroup $ringGroup, array $members): void
    {
        $syncData = [];
        foreach ($members as $member) {
            $syncData[$member['sip_account_id']] = [
                'priority' => $member['priority'],
                'delay'    => $member['delay'],
            ];
        }
        $ringGroup->members()->sync($syncData);
    }
}
