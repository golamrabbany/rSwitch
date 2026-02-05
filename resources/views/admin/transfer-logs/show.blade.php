<x-admin-layout>
    <x-slot name="header">Transfer Log #{{ $transferLog->id }}</x-slot>

    <div class="max-w-4xl">
        <div class="mb-4">
            <a href="{{ route('admin.transfer-logs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Transfer Logs</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Transfer Details --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Transfer Details</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                {{ match($transferLog->transfer_type) {
                                    'client_transfer' => 'bg-blue-50 text-blue-700',
                                    'did_transfer' => 'bg-purple-50 text-purple-700',
                                    'sip_transfer' => 'bg-green-50 text-green-700',
                                    default => 'bg-gray-50 text-gray-700',
                                } }}">
                                {{ ucfirst(str_replace('_', ' ', $transferLog->transfer_type)) }}
                            </span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Item Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transferLog->transferred_item_type ?? '-' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Item ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">#{{ $transferLog->transferred_item_id }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Date</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transferLog->created_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Parties --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Parties</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">From</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if ($transferLog->fromParent)
                                <a href="{{ route('admin.users.show', $transferLog->fromParent) }}" class="text-indigo-600 hover:text-indigo-900">{{ $transferLog->fromParent->name }}</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">To</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if ($transferLog->toParent)
                                <a href="{{ route('admin.users.show', $transferLog->toParent) }}" class="text-indigo-600 hover:text-indigo-900">{{ $transferLog->toParent->name }}</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Performed By</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if ($transferLog->performedBy)
                                <a href="{{ route('admin.users.show', $transferLog->performedBy) }}" class="text-indigo-600 hover:text-indigo-900">{{ $transferLog->performedBy->name }}</a>
                            @else
                                <span class="text-gray-400">System</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Reason</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transferLog->reason ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Metadata --}}
        @if ($transferLog->metadata)
            <div class="mt-6 bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Metadata</h3>
                </div>
                <div class="p-4">
                    <pre class="bg-gray-50 rounded-md p-4 text-xs font-mono text-gray-700 overflow-x-auto">{{ json_encode($transferLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
