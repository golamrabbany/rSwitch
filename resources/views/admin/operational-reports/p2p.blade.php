<x-admin-layout>
    <x-slot name="header">P2P Calls</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-500 flex items-center justify-center shadow-lg shadow-teal-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">P2P Calls</h2>
                <p class="page-subtitle">Internal SIP-to-SIP calls between accounts</p>
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

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Total Calls --}}
        <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-teal-100 text-xs font-medium uppercase tracking-wide">Total Calls</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($totalCalls) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Answered --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Answered</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($answeredCalls) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ASR --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">ASR</p>
                    <p class="text-3xl font-bold {{ $asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }} mt-1">{{ $asr }}%</p>
                </div>
                <div class="w-12 h-12 {{ $asr >= 50 ? 'bg-emerald-100' : 'bg-amber-100' }} rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Minutes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Minutes</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-1">{{ number_format($totalMinutes, 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            {{-- Search --}}
            <div class="flex-1 min-w-[180px] max-w-xs">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller/callee..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                </div>
            </div>

            {{-- Date Range --}}
            <input type="date" name="date_from" value="{{ request('date_from', now()->format('Y-m-d')) }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="End date">

            {{-- Disposition Filter --}}
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                <a href="{{ route('admin.operational-reports.p2p', array_merge(request()->except('disposition'), [])) }}"
                   class="px-3 py-2 text-sm font-medium {{ !request('disposition') ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    All
                </a>
                <a href="{{ route('admin.operational-reports.p2p', array_merge(request()->except('disposition'), ['disposition' => 'ANSWERED'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('disposition') === 'ANSWERED' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Answered
                </a>
                <a href="{{ route('admin.operational-reports.p2p', array_merge(request()->except('disposition'), ['disposition' => 'NO ANSWER'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('disposition') === 'NO ANSWER' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    No Answer
                </a>
                <a href="{{ route('admin.operational-reports.p2p', array_merge(request()->except('disposition'), ['disposition' => 'FAILED'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('disposition') === 'FAILED' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Failed
                </a>
            </div>

            <button type="submit" class="px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700">
                Search
            </button>

            @if(request()->hasAny(['search', 'disposition', 'date_to']))
                <a href="{{ route('admin.operational-reports.p2p') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Calls Table --}}
    @if($calls->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Table Header Info --}}
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">
                        Showing <span class="font-semibold">{{ $calls->firstItem() }}-{{ $calls->lastItem() }}</span> of <span class="font-semibold">{{ number_format($calls->total()) }}</span> calls
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Answered
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> No Answer
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span> Failed
                    </span>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller (SIP)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee (SIP)</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($calls as $call)
                            <tr class="hover:bg-gray-50 transition-colors">
                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    @switch($call->disposition)
                                        @case('ANSWERED')
                                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Answered
                                            </span>
                                            @break
                                        @case('NO ANSWER')
                                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                No Answer
                                            </span>
                                            @break
                                        @case('BUSY')
                                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                Busy
                                            </span>
                                            @break
                                        @case('FAILED')
                                        @case('CONGESTION')
                                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Failed
                                            </span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                {{ $call->disposition ?? 'Unknown' }}
                                            </span>
                                    @endswitch
                                </td>

                                {{-- Time --}}
                                <td class="px-4 py-3">
                                    <span class="text-gray-900">{{ $call->call_start->format('H:i:s') }}</span>
                                    <span class="text-xs text-gray-400 block">{{ $call->call_start->format('M d') }}</span>
                                </td>

                                {{-- Caller (SIP) --}}
                                <td class="px-4 py-3">
                                    <span class="font-mono text-gray-900">{{ $call->caller }}</span>
                                    @if($call->sipAccount)
                                        <a href="{{ route('admin.sip-accounts.show', $call->sipAccount) }}" class="text-xs text-teal-600 hover:text-teal-500 block">
                                            {{ $call->sipAccount->username }}
                                        </a>
                                    @endif
                                </td>

                                {{-- Callee (SIP) --}}
                                <td class="px-4 py-3">
                                    <span class="font-mono text-gray-900">{{ $call->callee }}</span>
                                </td>

                                {{-- User --}}
                                <td class="px-4 py-3">
                                    @if($call->user)
                                        <a href="{{ route('admin.users.show', $call->user) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                                            {{ Str::limit($call->user->name, 15) }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Duration --}}
                                <td class="px-4 py-3">
                                    @if($call->billsec > 0)
                                        <span class="font-medium text-gray-900">{{ gmdate('H:i:s', $call->billsec) }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($calls->hasPages())
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    {{ $calls->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-xl border border-gray-200 py-16">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-teal-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No P2P Calls Found</h3>
                <p class="text-gray-500 text-sm">Try adjusting your filters or date range</p>
            </div>
        </div>
    @endif
</x-admin-layout>
