<x-admin-layout>
    <x-slot name="header">Audit Log #{{ $auditLog->id }}</x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Log Details</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $auditLog->id }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">User</dt>
                    <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                        @if($auditLog->user)
                            <a href="{{ route('admin.users.show', $auditLog->user) }}" class="text-indigo-600 hover:text-indigo-500">{{ $auditLog->user->name }}</a>
                            <span class="text-gray-500">({{ $auditLog->user->email }})</span>
                        @else
                            <span class="text-gray-400">System</span>
                        @endif
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Action</dt>
                    <dd class="mt-1 sm:col-span-2 sm:mt-0">
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $auditLog->action }}</span>
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Entity</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ class_basename($auditLog->auditable_type) }} #{{ $auditLog->auditable_id }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Date</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0">{{ $auditLog->ip_address }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">User Agent</dt>
                    <dd class="mt-1 text-sm text-gray-500 sm:col-span-2 sm:mt-0 break-all">{{ $auditLog->user_agent ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Old vs New Values Diff --}}
        @if($auditLog->old_values || $auditLog->new_values)
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-4 border-b border-gray-200 bg-red-50">
                        <h3 class="text-sm font-semibold text-red-800">Old Values</h3>
                    </div>
                    <div class="p-4">
                        @if($auditLog->old_values)
                            <dl class="space-y-2">
                                @foreach ($auditLog->old_values as $key => $value)
                                    <div class="flex">
                                        <dt class="text-xs font-medium text-gray-500 w-40 shrink-0 font-mono">{{ $key }}</dt>
                                        <dd class="text-xs text-gray-900 break-all">
                                            @if(is_array($value))
                                                <pre class="bg-gray-50 rounded p-1">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $value ?? '(null)' }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-sm text-gray-400">No previous values (new record)</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-4 border-b border-gray-200 bg-green-50">
                        <h3 class="text-sm font-semibold text-green-800">New Values</h3>
                    </div>
                    <div class="p-4">
                        @if($auditLog->new_values)
                            <dl class="space-y-2">
                                @foreach ($auditLog->new_values as $key => $value)
                                    <div class="flex">
                                        <dt class="text-xs font-medium text-gray-500 w-40 shrink-0 font-mono">{{ $key }}</dt>
                                        <dd class="text-xs text-gray-900 break-all">
                                            @if(is_array($value))
                                                <pre class="bg-gray-50 rounded p-1">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                            @else
                                                {{ $value ?? '(null)' }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-sm text-gray-400">No new values</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">&larr; Back to Audit Logs</a>
    </div>
</x-admin-layout>
