<x-admin-layout>
    <x-slot name="header">Rate Imports</x-slot>

    {{-- Filters --}}
    <div class="bg-white shadow sm:rounded-lg mb-6">
        <form method="GET" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500">Rate Group</label>
                <select name="rate_group_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option value="">All Groups</option>
                    @foreach ($rateGroups as $group)
                        <option value="{{ $group->id }}" {{ request('rate_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Status</label>
                <select name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    <option value="">All</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
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
                <a href="{{ route('admin.rate-imports.index') }}" class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate Group</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">File</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uploaded By</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Imported</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Errors</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($imports as $import)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $import->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            @if ($import->rateGroup)
                                <a href="{{ route('admin.rate-groups.show', $import->rateGroup) }}" class="text-indigo-600 hover:text-indigo-900">{{ $import->rateGroup->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $import->file_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $import->uploader?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-mono">{{ number_format($import->total_rows ?? 0) }}</td>
                        <td class="px-4 py-3 text-sm text-green-600 text-right font-mono">{{ number_format($import->imported_rows ?? 0) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-mono {{ ($import->error_rows ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ number_format($import->error_rows ?? 0) }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                {{ match($import->status) {
                                    'completed' => 'bg-green-50 text-green-700',
                                    'failed' => 'bg-red-50 text-red-700',
                                    'processing' => 'bg-yellow-50 text-yellow-700',
                                    default => 'bg-gray-50 text-gray-700',
                                } }}">
                                {{ ucfirst($import->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('admin.rate-imports.show', $import) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">No rate imports found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-gray-200 px-4 py-3">
            {{ $imports->links() }}
        </div>
    </div>
</x-admin-layout>
