<x-admin-layout>
    <x-slot name="header">Rate Management</x-slot>

    {{-- Header with Create Button --}}
    <div class="flex items-center justify-between mb-6">
        <div></div>
        <a href="{{ route('admin.rate-groups.create') }}"
           class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Rate Group
        </a>
    </div>

    {{-- Filters --}}
    <div class="mb-6 bg-white shadow sm:rounded-lg p-4">
        <form method="GET" action="{{ route('admin.rate-groups.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                           placeholder="Rate group name..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="type" class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                    <select id="type" name="type" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Types</option>
                        <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="reseller" {{ request('type') === 'reseller' ? 'selected' : '' }}>Reseller</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'type']))
                        <a href="{{ route('admin.rate-groups.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rates</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($rateGroups as $group)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <a href="{{ route('admin.rate-groups.show', $group) }}" class="text-indigo-600 hover:text-indigo-500">
                                {{ $group->name }}
                            </a>
                            @if($group->description)
                                <p class="text-xs text-gray-500 truncate max-w-xs">{{ $group->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($group->type === 'admin')
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">Admin</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">Reseller</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">{{ number_format($group->rates_count) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">{{ number_format($group->users_count) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $group->creator?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right space-x-3">
                            <a href="{{ route('admin.rate-groups.show', $group) }}" class="text-indigo-600 hover:text-indigo-500">View</a>
                            <a href="{{ route('admin.rate-groups.edit', $group) }}" class="text-indigo-600 hover:text-indigo-500">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                            No rate groups found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rateGroups->hasPages())
        <div class="mt-4">
            {{ $rateGroups->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
