<x-admin-layout>
    <x-slot name="header">Audit Log Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-500 to-slate-700 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Audit Log #{{ $auditLog->id }}</h2>
                <p class="page-subtitle">{{ $auditLog->created_at->format('M d, Y \a\t H:i:s') }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.audit-logs.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Audit Logs
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Log Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Log Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Log ID</span>
                            <span class="detail-value font-mono">#{{ $auditLog->id }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Action</span>
                            <span class="detail-value">
                                <span class="badge badge-gray">{{ $auditLog->action }}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Entity</span>
                            <span class="detail-value">{{ class_basename($auditLog->auditable_type) }} <span class="font-mono">#{{ $auditLog->auditable_id }}</span></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date</span>
                            <span class="detail-value">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">IP Address</span>
                            <span class="detail-value font-mono">{{ $auditLog->ip_address }}</span>
                        </div>
                        <div class="detail-item col-span-2">
                            <span class="detail-label">User Agent</span>
                            <span class="detail-value text-xs text-gray-500 break-all">{{ $auditLog->user_agent ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Old vs New Values --}}
            @if($auditLog->old_values || $auditLog->new_values)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="detail-card">
                        <div class="detail-card-header audit-diff-old">
                            <h3 class="detail-card-title">Old Values</h3>
                        </div>
                        <div class="detail-card-body">
                            @if($auditLog->old_values)
                                <dl class="space-y-3">
                                    @foreach ($auditLog->old_values as $key => $value)
                                        <div class="flex gap-3">
                                            <dt class="text-xs font-medium text-gray-500 w-32 flex-shrink-0 font-mono">{{ $key }}</dt>
                                            <dd class="text-xs text-gray-900 break-all">
                                                @if(is_array($value))
                                                    <pre class="bg-gray-50 rounded p-2 text-xs overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
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

                    <div class="detail-card">
                        <div class="detail-card-header audit-diff-new">
                            <h3 class="detail-card-title">New Values</h3>
                        </div>
                        <div class="detail-card-body">
                            @if($auditLog->new_values)
                                <dl class="space-y-3">
                                    @foreach ($auditLog->new_values as $key => $value)
                                        <div class="flex gap-3">
                                            <dt class="text-xs font-medium text-gray-500 w-32 flex-shrink-0 font-mono">{{ $key }}</dt>
                                            <dd class="text-xs text-gray-900 break-all">
                                                @if(is_array($value))
                                                    <pre class="bg-gray-50 rounded p-2 text-xs overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
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
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Performed By</h3>
                </div>
                <div class="detail-card-body">
                    @if($auditLog->user)
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-semibold flex-shrink-0 bg-indigo-100 text-indigo-700">
                                {{ strtoupper(substr($auditLog->user->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('admin.users.show', $auditLog->user) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                    {{ $auditLog->user->name }}
                                </a>
                                <p class="text-xs text-gray-500 truncate">{{ $auditLog->user->email }}</p>
                                <span class="badge badge-gray mt-1">{{ ucfirst($auditLog->user->role) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 text-gray-500">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium">System</p>
                                <p class="text-xs text-gray-400">Automated action</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Info</h3>
                </div>
                <div class="detail-card-body space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Entity Type</span>
                        <span class="font-medium text-gray-900">{{ class_basename($auditLog->auditable_type) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Entity ID</span>
                        <span class="font-mono text-gray-900">#{{ $auditLog->auditable_id }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Changed Fields</span>
                        <span class="font-medium text-gray-900">{{ count($auditLog->new_values ?? []) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
