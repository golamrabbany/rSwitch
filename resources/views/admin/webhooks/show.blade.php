<x-admin-layout>
    <x-slot name="header">Webhook Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Webhook Endpoint</h2>
                <p class="page-subtitle break-all">{{ Str::limit($webhook->url, 50) }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <a href="{{ route('admin.webhooks.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Endpoint Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Endpoint Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item col-span-2">
                            <span class="detail-label">URL</span>
                            <span class="detail-value text-sm break-all">{{ $webhook->url }}</span>
                        </div>
                        @if ($webhook->description)
                            <div class="detail-item col-span-2">
                                <span class="detail-label">Description</span>
                                <span class="detail-value">{{ $webhook->description }}</span>
                            </div>
                        @endif
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                @if ($webhook->active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">{{ $webhook->created_at->format('M j, Y g:ia') }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="detail-label">Events</span>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($webhook->events as $event)
                                <span class="badge badge-purple">{{ $event }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Health Status --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Health Status</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Consecutive Failures</span>
                            <span class="detail-value {{ $webhook->failure_count > 0 ? 'text-red-600 font-semibold' : '' }}">
                                {{ $webhook->failure_count }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Triggered</span>
                            <span class="detail-value">{{ $webhook->last_triggered_at?->format('M j, Y g:ia') ?? 'Never' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Failed</span>
                            <span class="detail-value">{{ $webhook->last_failed_at?->format('M j, Y g:ia') ?? 'Never' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Delivery Logs --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Delivery Log</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>HTTP</th>
                                <th>Time (ms)</th>
                                <th>Attempt</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="text-gray-600 text-sm">{{ $log->created_at->format('M j g:ia') }}</td>
                                    <td class="text-sm">{{ $log->event }}</td>
                                    <td>
                                        @if ($log->status === 'success')
                                            <span class="badge badge-success">OK</span>
                                        @elseif ($log->status === 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @else
                                            <span class="badge badge-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td class="text-gray-600">{{ $log->response_code ?? '—' }}</td>
                                    <td class="text-gray-600">{{ $log->response_time_ms ?? '—' }}</td>
                                    <td class="text-gray-600">{{ $log->attempt }}</td>
                                    <td class="text-red-600 text-sm max-w-xs truncate">{{ $log->error ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-500">No delivery logs yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">User</h3>
                </div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-semibold flex-shrink-0 bg-indigo-100 text-indigo-700">
                            {{ strtoupper(substr($webhook->user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.users.show', $webhook->user) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                {{ $webhook->user->name }}
                            </a>
                            <p class="text-xs text-gray-500 truncate">{{ $webhook->user->email }}</p>
                            <span class="badge badge-gray mt-1">{{ ucfirst($webhook->user->role) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Secret --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Signing Secret</h3>
                </div>
                <div class="detail-card-body">
                    <p class="text-sm text-gray-600 mb-4">The signing secret is used to verify webhook authenticity. It was shown once on creation.</p>
                    <form method="POST" action="{{ route('admin.webhooks.regenerate-secret', $webhook) }}">
                        @csrf
                        <button type="submit" class="btn-warning w-full" onclick="return confirm('This will invalidate the current secret. Continue?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Regenerate Secret
                        </button>
                    </form>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Danger Zone</h3>
                </div>
                <div class="detail-card-body">
                    <form method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger w-full" onclick="return confirm('Delete this webhook endpoint?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Endpoint
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
