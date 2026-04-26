<x-admin-layout>
    <x-slot name="header">Call Summary</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Call Summary</h2>
                <p class="page-subtitle">Combined inbound and outbound statistics</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.operational-reports.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="filter-card mb-3">
        <form method="GET" class="flex items-center gap-3">
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input text-sm flex-1">
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input text-sm flex-1">
            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search
            </button>
            @if(request()->hasAny(['date_from', 'date_to']))
                <a href="{{ route('admin.operational-reports.summary') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Overall Stats --}}
    @php
        $failedCalls = $totalCalls - $answeredCalls;
        $totalDuration = $totalMinutes * 60;
        $acdSeconds = ($answeredCalls > 0) ? round($totalDuration / $answeredCalls) : 0;
        $acdMin = intdiv($acdSeconds, 60);
        $acdSec = $acdSeconds % 60;
    @endphp
    @php
        $asrColor = $asr >= 50 ? ['bg' => 'bg-emerald-100', 'icon' => 'text-emerald-600', 'val' => 'text-emerald-600'] : ['bg' => 'bg-amber-100', 'icon' => 'text-amber-600', 'val' => 'text-amber-600'];
    @endphp
    <div class="mb-4 grid grid-cols-3 sm:grid-cols-6 gap-2">
        <div class="flex items-center gap-1.5 px-2 py-1.5 bg-white rounded-md border border-gray-200 min-w-0">
            <div class="w-6 h-6 rounded bg-indigo-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-base font-semibold text-gray-900 leading-none tabular-nums">{{ number_format($totalCalls) }}</p>
                <p class="text-xs text-gray-500 truncate">Total</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 px-2 py-1.5 bg-white rounded-md border border-gray-200 min-w-0">
            <div class="w-6 h-6 rounded bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-base font-semibold text-emerald-600 leading-none tabular-nums">{{ number_format($answeredCalls) }}</p>
                <p class="text-xs text-gray-500 truncate">Answered</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 px-2 py-1.5 bg-white rounded-md border border-gray-200 min-w-0">
            <div class="w-6 h-6 rounded bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-base font-semibold text-red-500 leading-none tabular-nums">{{ number_format($failedCalls) }}</p>
                <p class="text-xs text-gray-500 truncate">Failed</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 px-2 py-1.5 bg-white rounded-md border border-gray-200 min-w-0">
            <div class="w-6 h-6 rounded {{ $asrColor['bg'] }} flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 {{ $asrColor['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-base font-semibold {{ $asrColor['val'] }} leading-none tabular-nums">{{ $asr }}%</p>
                <p class="text-xs text-gray-500 truncate">ASR</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 px-2 py-1.5 bg-white rounded-md border border-gray-200 min-w-0">
            <div class="w-6 h-6 rounded bg-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-base font-semibold text-gray-900 leading-none tabular-nums">{{ $acdMin }}:{{ sprintf('%02d', $acdSec) }}</p>
                <p class="text-xs text-gray-500 truncate">ACD</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 px-2 py-1.5 bg-white rounded-md border border-gray-200 min-w-0">
            <div class="w-6 h-6 rounded bg-indigo-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-base font-semibold text-gray-900 leading-none tabular-nums">{{ number_format($totalMinutes, 0) }}</p>
                <p class="text-xs text-gray-500 truncate">Minutes</p>
            </div>
        </div>
    </div>

    {{-- Inbound vs Outbound Comparison --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Inbound Stats Card --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-blue-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Inbound Calls</h3>
                        <p class="text-sm text-gray-500">Calls received from trunks</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-blue-600">{{ number_format($inboundTotal) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Calls</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-emerald-600">{{ number_format($inboundAnswered) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Answered</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold {{ $inboundAsr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $inboundAsr }}%</p>
                        <p class="text-xs text-gray-500 mt-1">ASR</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-indigo-600">{{ number_format($inboundMinutes, 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Minutes</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                    <a href="{{ route('admin.operational-reports.inbound', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="text-sm text-blue-600 hover:text-blue-500 font-medium">
                        View Inbound Details
                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Outbound Stats Card --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-purple-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-400 to-purple-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Outbound Calls</h3>
                        <p class="text-sm text-gray-500">Calls sent to destinations</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-purple-600">{{ number_format($outboundTotal) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Calls</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-emerald-600">{{ number_format($outboundAnswered) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Answered</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold {{ $outboundAsr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $outboundAsr }}%</p>
                        <p class="text-xs text-gray-500 mt-1">ASR</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-3xl font-bold text-indigo-600">{{ number_format($outboundMinutes, 0) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Minutes</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                    <a href="{{ route('admin.operational-reports.outbound', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="text-sm text-purple-600 hover:text-purple-500 font-medium">
                        View Outbound Details
                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Disposition Breakdown & Top Lists --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Disposition Breakdown --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Disposition Breakdown</h3>
            </div>
            <div class="p-4">
                @if(count($dispositions) > 0)
                    <div class="space-y-3">
                        @foreach($dispositions as $disposition => $count)
                            @php
                                $percentage = $totalCalls > 0 ? round(($count / $totalCalls) * 100, 1) : 0;
                                $colorClass = match($disposition) {
                                    'ANSWERED' => 'bg-emerald-500',
                                    'NO ANSWER' => 'bg-amber-500',
                                    'BUSY' => 'bg-blue-500',
                                    'FAILED', 'CONGESTION' => 'bg-red-500',
                                    default => 'bg-gray-400'
                                };
                                $textColor = match($disposition) {
                                    'ANSWERED' => 'text-emerald-600',
                                    'NO ANSWER' => 'text-amber-600',
                                    'BUSY' => 'text-blue-600',
                                    'FAILED', 'CONGESTION' => 'text-red-600',
                                    default => 'text-gray-600'
                                };
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-gray-700">{{ $disposition ?? 'Unknown' }}</span>
                                    <span class="text-sm font-semibold {{ $textColor }}">{{ number_format($count) }} ({{ $percentage }}%)</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">No data available</p>
                @endif
            </div>
        </div>

        {{-- Top SIP Accounts --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Top SIP Accounts</h3>
            </div>
            @if($topSipAccounts->count() > 0)
                <div class="divide-y divide-gray-50">
                    @foreach($topSipAccounts as $index => $item)
                        @if($item->sipAccount)
                            <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50">
                                <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('admin.sip-accounts.show', $item->sipAccount) }}" class="text-sm text-gray-900 hover:text-indigo-600 truncate block">
                                        {{ $item->sipAccount->username }}
                                    </a>
                                    <span class="text-xs text-gray-400">{{ number_format($item->duration / 60, 0) }} min</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-500">{{ $item->call_count }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="p-4 text-center">
                    <p class="text-sm text-gray-400">No activity</p>
                </div>
            @endif
        </div>

        {{-- Top Trunks --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Top Trunks</h3>
            </div>
            @if($topTrunks->count() > 0)
                <div class="divide-y divide-gray-50">
                    @foreach($topTrunks as $index => $item)
                        @if($item->trunk)
                            <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50">
                                <span class="w-5 h-5 rounded-full {{ $item->call_flow === 'trunk_to_sip' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }} text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('admin.trunks.show', $item->trunk) }}" class="text-sm text-gray-900 hover:text-indigo-600 truncate block">
                                        {{ $item->trunk->name }}
                                    </a>
                                    <span class="text-xs {{ $item->call_flow === 'trunk_to_sip' ? 'text-blue-500' : 'text-purple-500' }}">
                                        {{ $item->call_flow === 'trunk_to_sip' ? 'Inbound' : 'Outbound' }}
                                    </span>
                                </div>
                                <span class="text-sm font-semibold text-gray-500">{{ $item->call_count }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="p-4 text-center">
                    <p class="text-sm text-gray-400">No activity</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Top Destinations --}}
    @if($topDestinations->count() > 0)
        <div class="mt-6 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Top Destination Prefixes (Outbound)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Prefix</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Calls</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Minutes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Avg Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($topDestinations as $index => $dest)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-600 text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono font-medium text-gray-900">{{ $dest->prefix }}xxx</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format($dest->count) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format($dest->duration / 60, 0) }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if($dest->count > 0)
                                        {{ gmdate('i:s', $dest->duration / $dest->count) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
