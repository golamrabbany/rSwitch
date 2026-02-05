<x-admin-layout>
    <x-slot name="header">Audit Logs</x-slot>

    {{-- Filters --}}
    <div class="mb-6 bg-white shadow sm:rounded-lg p-4">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label for="user_id" class="block text-xs font-medium text-gray-500 mb-1">User</label>
                    <select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="action" class="block text-xs font-medium text-gray-500 mb-1">Action</label>
                    <input type="text" id="action" name="action" value="{{ request('action') }}" placeholder="e.g. user.created"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="entity_type" class="block text-xs font-medium text-gray-500 mb-1">Entity Type</label>
                    <select id="entity_type" name="entity_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Types</option>
                        @foreach ($entityTypes as $type)
                            <option value="{{ $type }}" {{ request('entity_type') === $type ? 'selected' : '' }}>{{ class_basename($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            @if($log->user)
                                <a href="{{ route('admin.users.show', $log->user_id) }}" class="text-indigo-600 hover:text-indigo-500">{{ $log->user->name }}</a>
                            @else
                                <span class="text-gray-400">System</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $log->action }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ $log->ip_address }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('admin.audit-logs.show', $log) }}" class="text-indigo-600 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No audit logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="mt-4">{{ $logs->withQueryString()->links() }}</div>
    @endif
</x-admin-layout>
