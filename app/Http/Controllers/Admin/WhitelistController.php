<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DestinationWhitelist;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class WhitelistController extends Controller
{
    public function index(Request $request)
    {
        $query = DestinationWhitelist::with('user', 'creator');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prefix', 'like', "{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $entries = $query->orderBy('created_at', 'desc')->paginate(20);

        $users = User::whereIn('role', ['reseller', 'client'])->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.whitelist.index', compact('entries', 'users'));
    }

    public function create()
    {
        $users = User::whereIn('role', ['reseller', 'client'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.whitelist.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'prefix' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $entry = DestinationWhitelist::create([
            'user_id' => $validated['user_id'],
            'prefix' => $validated['prefix'],
            'description' => $validated['description'] ?? '',
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        AuditService::logCreated($entry, 'whitelist.created');

        return redirect()->route('admin.whitelist.index')
            ->with('success', "Whitelist entry for prefix {$validated['prefix']} created.");
    }

    public function edit(DestinationWhitelist $whitelist)
    {
        $users = User::whereIn('role', ['reseller', 'client'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.whitelist.edit', compact('whitelist', 'users'));
    }

    public function update(Request $request, DestinationWhitelist $whitelist)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'prefix' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $original = $whitelist->getAttributes();

        $whitelist->update([
            'user_id' => $validated['user_id'],
            'prefix' => $validated['prefix'],
            'description' => $validated['description'] ?? '',
        ]);

        AuditService::logUpdated($whitelist, $original, 'whitelist.updated');

        return redirect()->route('admin.whitelist.index')
            ->with('success', 'Whitelist entry updated.');
    }

    public function destroy(DestinationWhitelist $whitelist)
    {
        AuditService::logAction('whitelist.deleted', $whitelist, $whitelist->toArray());

        $whitelist->delete();

        return redirect()->route('admin.whitelist.index')
            ->with('success', 'Whitelist entry deleted.');
    }
}
