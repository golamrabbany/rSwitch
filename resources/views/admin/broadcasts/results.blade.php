<x-admin-layout>
    <x-slot name="header">Broadcast Results</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $broadcast->name }} - Results</h2>
                <div class="flex items-center gap-2 mt-1">
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
                    @if($broadcast->user)
                        <span class="text-sm text-gray-500">by</span>
                        <a href="{{ route('admin.users.show', $broadcast->user) }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                            {{ $broadcast->user->name }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.broadcasts.show', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Broadcast
            </a>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($stats['total'] ?? $broadcast->total_numbers) }}</p>
                <p class="stat-label">Total</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($stats['answered'] ?? $broadcast->answered_count) }}</p>
                <p class="stat-label">Answered</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red-100">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($stats['failed'] ?? $broadcast->failed_count) }}</p>
                <p class="stat-label">Failed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ format_currency($stats['cost'] ?? $broadcast->total_cost ?? 0) }}</p>
                <p class="stat-label">Cost</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ $stats['avg_duration'] ?? '0s' }}</p>
                <p class="stat-label">Avg Duration</p>
            </div>
        </div>
    </div>

    {{-- Survey Response Breakdown --}}
    @if($broadcast->type === 'survey' && !empty($surveyBreakdown))
        <div class="detail-card mb-6">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Survey Response Breakdown</h3>
            </div>
            <div class="detail-card-body">
                <div class="space-y-3">
                    @foreach($surveyBreakdown as $option)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <span class="text-sm font-bold text-indigo-600">{{ $option['digit'] }}</span>
                                </div>
                                <span class="text-sm text-gray-700">{{ $option['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $option['percentage'] }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 w-16 text-right">{{ $option['count'] }} ({{ $option['percentage'] }}%)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Results Table --}}
    <div class="data-table-container">
        @if($results->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $results->firstItem() }}–{{ $results->lastItem() }}</span> of <span class="font-semibold">{{ number_format($results->total()) }}</span> results
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th style="text-align: right">Attempts</th>
                    <th style="text-align: right">Duration</th>
                    <th style="text-align: right">Cost</th>
                    @if($broadcast->type === 'survey')
                        <th>Survey Response</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($results as $result)
                    <tr>
                        <td class="text-sm font-medium text-gray-900">{{ $result->phone_number }}</td>
                        <td>
                            @switch($result->status)
                                @case('answered')
                                    <span class="badge badge-success">Answered</span>
                                    @break
                                @case('no_answer')
                                    <span class="badge badge-warning">No Answer</span>
                                    @break
                                @case('busy')
                                    <span class="badge badge-warning">Busy</span>
                                    @break
                                @case('failed')
                                    <span class="badge badge-danger">Failed</span>
                                    @break
                                @case('pending')
                                    <span class="badge badge-gray">Pending</span>
                                    @break
                                @default
                                    <span class="badge badge-gray">{{ ucfirst($result->status) }}</span>
                            @endswitch
                        </td>
                        <td style="text-align: right" class="text-sm text-gray-700">{{ $result->attempts }}</td>
                        <td style="text-align: right" class="text-sm text-gray-700">
                            @if($result->duration)
                                {{ floor($result->duration / 60) }}m {{ $result->duration % 60 }}s
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                        <td style="text-align: right" class="text-sm text-gray-700">
                            {{ format_currency($result->cost ?? 0) }}
                        </td>
                        @if($broadcast->type === 'survey')
                            <td class="text-sm text-gray-700">
                                @if($result->survey_response)
                                    <span class="badge badge-purple">{{ $result->survey_response }}</span>
                                @else
                                    <span class="text-gray-400">--</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $broadcast->type === 'survey' ? 6 : 5 }}" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="empty-text">No results yet</p>
                                <p class="text-sm text-gray-400">Results will appear once the broadcast starts dialing</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($results->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $results->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-admin-layout>
