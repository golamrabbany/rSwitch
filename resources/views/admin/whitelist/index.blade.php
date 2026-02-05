<x-admin-layout>
    <x-slot name="header">Destination Whitelist</x-slot>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Prefix or description..."
                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select name="user_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Search</button>
            @if(request()->hasAny(['search', 'user_id']))
                <a href="{{ route('admin.whitelist.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
        <a href="{{ route('admin.whitelist.create') }}" class="ml-auto rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Add Entry</a>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prefix</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($entries as $entry)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900">{{ $entry->prefix }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ Str::limit($entry->description, 40) ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($entry->user)
                                <a href="{{ route('admin.users.show', $entry->user_id) }}" class="text-indigo-600 hover:text-indigo-500">{{ $entry->user->name }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $entry->creator?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $entry->created_at?->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                            <a href="{{ route('admin.whitelist.edit', $entry) }}" class="text-indigo-600 hover:text-indigo-500">Edit</a>
                            <form method="POST" action="{{ route('admin.whitelist.destroy', $entry) }}" class="inline ml-3">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-500" onclick="return confirm('Delete this whitelist entry?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No whitelist entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($entries->hasPages())
        <div class="mt-4">{{ $entries->withQueryString()->links() }}</div>
    @endif
</x-admin-layout>
