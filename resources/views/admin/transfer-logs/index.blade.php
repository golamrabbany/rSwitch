<x-admin-layout>
    <x-slot name="header">Transfer Logs</x-slot>

    {{-- Filters --}}
    <div class="bg-white shadow sm:rounded-lg mb-6">
        <form method="GET" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500">Type</label>
                <select name="transfer_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option value="">All Types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" {{ request('transfer_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Performed By</label>
                <select name="performed_by" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option value="">All Users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ request('performed_by') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Filter</button>
                <a href="{{ route('admin.transfer-logs.index') }}" class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                {{ match($log->transfer_type) {
                                    'client_transfer' => 'bg-blue-50 text-blue-700',
                                    'did_transfer' => 'bg-purple-50 text-purple-700',
                                    'sip_transfer' => 'bg-green-50 text-green-700',
                                    default => 'bg-gray-50 text-gray-700',
                                } }}">
                                {{ ucfirst(str_replace('_', ' ', $log->transfer_type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ class_basename($log->transferred_item_type ?? '') }} #{{ $log->transferred_item_id }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->fromParent?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->toParent?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $log->performedBy?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $log->reason ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('admin.transfer-logs.show', $log) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No transfer logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-gray-200 px-4 py-3">
            {{ $logs->links() }}
        </div>
    </div>
</x-admin-layout>
