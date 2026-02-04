<x-admin-layout>
    <x-slot name="header">Trunks</x-slot>

    {{-- Filters --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="direction" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Directions</option>
                <option value="incoming" {{ request('direction') === 'incoming' ? 'selected' : '' }}>Incoming</option>
                <option value="outgoing" {{ request('direction') === 'outgoing' ? 'selected' : '' }}>Outgoing</option>
                <option value="both" {{ request('direction') === 'both' ? 'selected' : '' }}>Both</option>
            </select>
            <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                <option value="auto_disabled" {{ request('status') === 'auto_disabled' ? 'selected' : '' }}>Auto-disabled</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, provider, or host..."
                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-64">
            <button type="submit" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Search</button>
            @if(request()->hasAny(['direction', 'status', 'search']))
                <a href="{{ route('admin.trunks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
        <a href="{{ route('admin.trunks.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Create Trunk
        </a>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Direction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Host</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channels</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Health</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($trunks as $trunk)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $trunk->name }}</div>
                            <div class="text-xs text-gray-500">{{ $trunk->provider }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $trunk->direction === 'outgoing' ? 'bg-green-100 text-green-800' : ($trunk->direction === 'incoming' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                {{ ucfirst($trunk->direction) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono text-gray-900">{{ $trunk->host }}:{{ $trunk->port }}</div>
                            <div class="text-xs text-gray-500">{{ strtoupper($trunk->transport) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $trunk->max_channels }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ in_array($trunk->direction, ['outgoing', 'both']) ? $trunk->outgoing_priority : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $trunk->health_status === 'up' ? 'bg-green-100 text-green-800' : ($trunk->health_status === 'down' ? 'bg-red-100 text-red-800' : ($trunk->health_status === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($trunk->health_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $trunk->status === 'active' ? 'bg-green-100 text-green-800' : ($trunk->status === 'auto_disabled' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                                {{ $trunk->status === 'auto_disabled' ? 'Auto-disabled' : ucfirst($trunk->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="{{ route('admin.trunks.show', $trunk) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                            <a href="{{ route('admin.trunks.edit', $trunk) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">No trunks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $trunks->withQueryString()->links() }}
    </div>
</x-admin-layout>
