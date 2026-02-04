<x-admin-layout>
    <x-slot name="header">DIDs</x-slot>

    {{-- Filter bar --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.dids.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <select name="status" onchange="this.form.submit()"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="unassigned" {{ request('status') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>

            <select name="trunk_id" onchange="this.form.submit()"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Trunks</option>
                @foreach ($trunks as $trunk)
                    <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>
                        {{ $trunk->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search number, provider, or owner..."
                   class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm w-64">

            <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Search
            </button>

            @if (request()->hasAny(['status', 'trunk_id', 'search']))
                <a href="{{ route('admin.dids.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>

        <a href="{{ route('admin.dids.create') }}"
           class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            + Add DID
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trunk</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost / Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($dids as $did)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono text-gray-900">
                            {{ $did->number }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $did->provider }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($did->trunk)
                                <a href="{{ route('admin.trunks.show', $did->trunk) }}" class="text-indigo-600 hover:text-indigo-500">
                                    {{ $did->trunk->name }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($did->assignedUser)
                                <a href="{{ route('admin.users.show', $did->assignedUser) }}" class="text-indigo-600 hover:text-indigo-500">
                                    {{ $did->assignedUser->name }}
                                </a>
                                <span class="text-xs text-gray-500">({{ ucfirst($did->assignedUser->role) }})</span>
                            @else
                                <span class="text-gray-400 italic">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($did->destination_type === 'sip_account' && $did->destination_id)
                                <span class="inline-flex items-center gap-1">
                                    <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">SIP</span>
                                    {{ $did->destination_id }}
                                </span>
                            @elseif ($did->destination_type === 'external' && $did->destination_number)
                                <span class="inline-flex items-center gap-1">
                                    <span class="text-xs font-medium text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded">EXT</span>
                                    <span class="font-mono text-xs">{{ $did->destination_number }}</span>
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            ${{ number_format($did->monthly_cost, 2) }} / ${{ number_format($did->monthly_price, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($did->status === 'active')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                            @elseif ($did->status === 'unassigned')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Unassigned</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Disabled</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right space-x-2">
                            <a href="{{ route('admin.dids.show', $did) }}" class="text-indigo-600 hover:text-indigo-500">View</a>
                            <a href="{{ route('admin.dids.edit', $did) }}" class="text-indigo-600 hover:text-indigo-500">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No DIDs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($dids->hasPages())
        <div class="mt-4">
            {{ $dids->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
