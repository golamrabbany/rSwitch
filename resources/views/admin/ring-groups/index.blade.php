<x-admin-layout>
    <x-slot name="header">Ring Groups</x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <form method="GET" class="flex items-center gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name..."
                       class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm w-48">
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
                <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Filter</button>
            </form>
            <a href="{{ route('admin.ring-groups.create') }}"
               class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Add Ring Group
            </a>
        </div>

        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Strategy</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Members</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Owner</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Timeout</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($ringGroups as $rg)
                        <tr>
                            <td class="px-3 py-4 text-sm">
                                <a href="{{ route('admin.ring-groups.show', $rg) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    {{ $rg->name }}
                                </a>
                                @if ($rg->description)
                                    <p class="text-xs text-gray-500 truncate max-w-xs">{{ $rg->description }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $rg->strategy === 'simultaneous' ? 'bg-blue-100 text-blue-700' : ($rg->strategy === 'sequential' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ ucfirst($rg->strategy) }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">{{ $rg->members_count }}</td>
                            <td class="px-3 py-4 text-sm text-gray-500">{{ $rg->user?->name ?? '—' }}</td>
                            <td class="px-3 py-4 text-sm text-gray-500">{{ $rg->ring_timeout }}s</td>
                            <td class="px-3 py-4 text-sm">
                                @if ($rg->status === 'active')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Disabled</span>
                                @endif
                            </td>
                            <td class="py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <a href="{{ route('admin.ring-groups.edit', $rg) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">No ring groups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $ringGroups->withQueryString()->links() }}</div>
    </div>
</x-admin-layout>
