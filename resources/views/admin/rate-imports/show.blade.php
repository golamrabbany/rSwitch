<x-admin-layout>
    <x-slot name="header">Rate Import #{{ $rateImport->id }}</x-slot>

    <div class="max-w-4xl">
        <div class="mb-4">
            <a href="{{ route('admin.rate-imports.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Rate Imports</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Import Details --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Import Details</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Rate Group</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if ($rateImport->rateGroup)
                                <a href="{{ route('admin.rate-groups.show', $rateImport->rateGroup) }}" class="text-indigo-600 hover:text-indigo-900">{{ $rateImport->rateGroup->name }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">File Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $rateImport->file_name }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Uploaded By</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if ($rateImport->uploader)
                                <a href="{{ route('admin.users.show', $rateImport->uploader) }}" class="text-indigo-600 hover:text-indigo-900">{{ $rateImport->uploader->name }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Effective Date</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $rateImport->effective_date?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $rateImport->created_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Completed</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $rateImport->completed_at?->format('Y-m-d H:i:s') ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Statistics --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Statistics</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                {{ match($rateImport->status) {
                                    'completed' => 'bg-green-50 text-green-700',
                                    'failed' => 'bg-red-50 text-red-700',
                                    'processing' => 'bg-yellow-50 text-yellow-700',
                                    default => 'bg-gray-50 text-gray-700',
                                } }}">
                                {{ ucfirst($rateImport->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Total Rows</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-mono">{{ number_format($rateImport->total_rows ?? 0) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Imported</dt>
                        <dd class="mt-1 text-sm text-green-600 sm:col-span-2 sm:mt-0 font-mono">{{ number_format($rateImport->imported_rows ?? 0) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Skipped</dt>
                        <dd class="mt-1 text-sm text-yellow-600 sm:col-span-2 sm:mt-0 font-mono">{{ number_format($rateImport->skipped_rows ?? 0) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Errors</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0 font-mono {{ ($rateImport->error_rows ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ number_format($rateImport->error_rows ?? 0) }}</dd>
                    </div>
                    @if (($rateImport->total_rows ?? 0) > 0)
                        <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Success Rate</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-mono">
                                {{ number_format(($rateImport->imported_rows / $rateImport->total_rows) * 100, 1) }}%
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Error Log --}}
        @if ($rateImport->error_log && count($rateImport->error_log) > 0)
            <div class="mt-6 bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Error Log ({{ count($rateImport->error_log) }} errors)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach (array_slice($rateImport->error_log, 0, 100) as $error)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-500 whitespace-nowrap font-mono">{{ $error['row'] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-red-600">{{ $error['error'] ?? $error['message'] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-400 font-mono max-w-md truncate">{{ $error['data'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if (count($rateImport->error_log) > 100)
                        <div class="px-4 py-3 text-sm text-gray-500">
                            Showing first 100 of {{ count($rateImport->error_log) }} errors.
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
