<x-admin-layout>
    <x-slot name="header">Webhook: {{ Str::limit($webhook->url, 50) }}</x-slot>

    <div class="space-y-6">
        {{-- Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <h3 class="text-base font-semibold text-gray-900">Endpoint Details</h3>

                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">URL</dt>
                        <dd class="text-sm text-gray-900 break-all">{{ $webhook->url }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">User</dt>
                        <dd class="text-sm text-gray-900">
                            <a href="{{ route('admin.users.show', $webhook->user) }}" class="text-indigo-600 hover:text-indigo-900">{{ $webhook->user->name }}</a>
                            ({{ $webhook->user->role }})
                        </dd>
                    </div>
                    @if ($webhook->description)
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Description</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->description }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Status</dt>
                        <dd>
                            @if ($webhook->active)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Events</dt>
                        <dd class="mt-1 flex flex-wrap gap-1">
                            @foreach ($webhook->events as $event)
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $event }}</span>
                            @endforeach
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Created</dt>
                        <dd class="text-sm text-gray-900">{{ $webhook->created_at->format('M j, Y g:ia') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <h3 class="text-base font-semibold text-gray-900">Health</h3>

                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Consecutive Failures</dt>
                        <dd class="text-sm {{ $webhook->failure_count > 0 ? 'text-red-600 font-semibold' : 'text-gray-900' }}">{{ $webhook->failure_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Last Triggered</dt>
                        <dd class="text-sm text-gray-900">{{ $webhook->last_triggered_at?->format('M j, Y g:ia') ?? 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Last Failed</dt>
                        <dd class="text-sm text-gray-900">{{ $webhook->last_failed_at?->format('M j, Y g:ia') ?? 'Never' }}</dd>
                    </div>
                </dl>

                <hr class="border-gray-200">

                <h3 class="text-base font-semibold text-gray-900">Secret</h3>
                <p class="text-xs text-gray-500">The signing secret is used to verify webhook authenticity. It was shown once on creation.</p>

                <form method="POST" action="{{ route('admin.webhooks.regenerate-secret', $webhook) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-yellow-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-yellow-500"
                            onclick="return confirm('This will invalidate the current secret. Continue?')">
                        Regenerate Secret
                    </button>
                </form>

                <hr class="border-gray-200">

                <div class="flex gap-3">
                    <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">Edit</a>
                    <form method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500"
                                onclick="return confirm('Delete this webhook endpoint?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Delivery Logs --}}
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b bg-gray-50">
                <h3 class="text-base font-semibold text-gray-900">Delivery Log</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">HTTP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time (ms)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempt</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $log->created_at->format('M j g:ia') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $log->event }}</td>
                            <td class="px-4 py-2">
                                @if ($log->status === 'success')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">OK</span>
                                @elseif ($log->status === 'pending')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Pending</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Failed</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $log->response_code ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $log->response_time_ms ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $log->attempt }}</td>
                            <td class="px-4 py-2 text-sm text-red-600 max-w-xs truncate">{{ $log->error ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No delivery logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-4 py-3 border-t">{{ $logs->links() }}</div>
        </div>
    </div>
</x-admin-layout>
