<x-client-layout>
    <x-slot name="header">Broadcasts</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">My Broadcasts</h2>
            <p class="page-subtitle">Manage your voice broadcast campaigns</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.broadcasts.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Broadcast
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        @if($broadcasts->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $broadcasts->firstItem() }}–{{ $broadcasts->lastItem() }}</span> of <span class="font-semibold">{{ number_format($broadcasts->total()) }}</span> broadcasts
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th style="text-align: right">Total Numbers</th>
                    <th>Progress</th>
                    <th style="text-align: right">Cost</th>
                    <th>Created</th>
                    <th style="text-align: center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($broadcasts as $broadcast)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-indigo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name">{{ $broadcast->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($broadcast->type === 'survey')
                                <span class="badge badge-purple">Survey</span>
                            @else
                                <span class="badge badge-blue">Simple</span>
                            @endif
                        </td>
                        <td>
                            @switch($broadcast->status)
                                @case('draft') <span class="badge badge-gray">Draft</span> @break
                                @case('scheduled') <span class="badge badge-blue">Scheduled</span> @break
                                @case('queued') <span class="badge badge-blue">Queued</span> @break
                                @case('running') <span class="badge badge-success">Running</span> @break
                                @case('paused') <span class="badge badge-warning">Paused</span> @break
                                @case('completed') <span class="badge badge-success">Completed</span> @break
                                @case('cancelled') <span class="badge badge-gray">Cancelled</span> @break
                                @case('failed') <span class="badge badge-danger">Failed</span> @break
                            @endswitch
                        </td>
                        <td style="text-align: right" class="text-sm text-gray-700">
                            {{ number_format($broadcast->total_numbers) }}
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $broadcast->total_numbers > 0 ? round($broadcast->answered_count / $broadcast->total_numbers * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $broadcast->answered_count }}/{{ $broadcast->total_numbers }}</span>
                            </div>
                        </td>
                        <td style="text-align: right" class="text-sm text-gray-700">
                            {{ format_currency($broadcast->total_cost ?? 0) }}
                        </td>
                        <td class="text-sm text-gray-500">
                            <div>{{ $broadcast->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $broadcast->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('client.broadcasts.show', $broadcast) }}" class="action-icon" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                <p class="empty-text">No broadcasts yet</p>
                                <p class="text-sm text-gray-400 mt-1">
                                    <a href="{{ route('client.broadcasts.create') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Create your first broadcast</a> to get started
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($broadcasts->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $broadcasts->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-client-layout>
