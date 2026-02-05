<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DestinationBlacklist;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlacklistController extends Controller
{
    public function index(Request $request)
    {
        $query = DestinationBlacklist::with('user', 'creator');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prefix', 'like', "{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('applies_to')) {
            $query->where('applies_to', $request->applies_to);
        }

        $entries = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.blacklist.index', compact('entries'));
    }

    public function create()
    {
        $users = User::whereIn('role', ['reseller', 'client'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.blacklist.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'applies_to' => ['required', Rule::in(['all', 'specific_users'])],
            'user_id' => ['required_if:applies_to,specific_users', 'nullable', 'exists:users,id'],
        ]);

        $entry = DestinationBlacklist::create([
            'prefix' => $validated['prefix'],
            'description' => $validated['description'] ?? '',
            'applies_to' => $validated['applies_to'],
            'user_id' => $validated['applies_to'] === 'specific_users' ? $validated['user_id'] : null,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        AuditService::logCreated($entry, 'blacklist.created');

        return redirect()->route('admin.blacklist.index')
            ->with('success', "Blacklist entry for prefix {$validated['prefix']} created.");
    }

    public function edit(DestinationBlacklist $blacklist)
    {
        $users = User::whereIn('role', ['reseller', 'client'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.blacklist.edit', compact('blacklist', 'users'));
    }

    public function update(Request $request, DestinationBlacklist $blacklist)
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'applies_to' => ['required', Rule::in(['all', 'specific_users'])],
            'user_id' => ['required_if:applies_to,specific_users', 'nullable', 'exists:users,id'],
        ]);

        $original = $blacklist->getAttributes();

        $blacklist->update([
            'prefix' => $validated['prefix'],
            'description' => $validated['description'] ?? '',
            'applies_to' => $validated['applies_to'],
            'user_id' => $validated['applies_to'] === 'specific_users' ? $validated['user_id'] : null,
        ]);

        AuditService::logUpdated($blacklist, $original, 'blacklist.updated');

        return redirect()->route('admin.blacklist.index')
            ->with('success', 'Blacklist entry updated.');
    }

    public function destroy(DestinationBlacklist $blacklist)
    {
        AuditService::logAction('blacklist.deleted', $blacklist, $blacklist->toArray());

        $blacklist->delete();

        return redirect()->route('admin.blacklist.index')
            ->with('success', 'Blacklist entry deleted.');
    }
}
