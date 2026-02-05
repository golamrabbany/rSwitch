<x-admin-layout>
    <x-slot name="header">Webhook Endpoints</x-slot>

    <div class="space-y-4">
        {{-- Filters --}}
        <div class="bg-white shadow sm:rounded-lg p-4">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="URL or description..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Status</label>
                    <select name="status" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-500">Filter</button>
                <a href="{{ route('admin.webhooks.create') }}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">+ New Endpoint</a>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Events</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Failures</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Triggered</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($endpoints as $ep)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900 max-w-xs truncate">
                                <a href="{{ route('admin.webhooks.show', $ep) }}" class="text-indigo-600 hover:text-indigo-900">{{ $ep->url }}</a>
                                @if ($ep->description)
                                    <p class="text-xs text-gray-500">{{ $ep->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $ep->user->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ count($ep->events) }} events</td>
                            <td class="px-4 py-3">
                                @if ($ep->active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm {{ $ep->failure_count > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">{{ $ep->failure_count }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $ep->last_triggered_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.webhooks.edit', $ep) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No webhook endpoints configured.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-4 py-3 border-t">{{ $endpoints->links() }}</div>
        </div>
    </div>
</x-admin-layout>
