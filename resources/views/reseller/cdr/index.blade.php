<x-reseller-layout>
    <x-slot name="header">CDR / Call Records</x-slot>

    {{-- Stats Panel --}}
    @php
        $asr = $stats['total_calls'] > 0 ? ($stats['answered_calls'] / $stats['total_calls']) * 100 : 0;
        $totalDur = (int) $stats['total_duration'];
        $totalBill = (int) $stats['total_billable'];
    @endphp
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-6">
        <div class="bg-white shadow sm:rounded-lg p-4">
            <dt class="text-sm font-medium text-gray-500">Total Calls</dt>
            <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($stats['total_calls']) }}</dd>
        </div>
        <div class="bg-white shadow sm:rounded-lg p-4">
            <dt class="text-sm font-medium text-gray-500">Answered</dt>
            <dd class="mt-1 text-2xl font-semibold text-green-600">
                {{ number_format($stats['answered_calls']) }}
                <span class="text-sm font-normal text-gray-500">({{ number_format($asr, 1) }}% ASR)</span>
            </dd>
        </div>
        <div class="bg-white shadow sm:rounded-lg p-4">
            <dt class="text-sm font-medium text-gray-500">Total Duration</dt>
            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                {{ sprintf('%d:%02d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60), $totalDur % 60) }}
            </dd>
        </div>
        <div class="bg-white shadow sm:rounded-lg p-4">
            <dt class="text-sm font-medium text-gray-500">Billable Duration</dt>
            <dd class="mt-1 text-2xl font-semibold text-gray-900">
                {{ sprintf('%d:%02d:%02d', intdiv($totalBill, 3600), intdiv($totalBill % 3600, 60), $totalBill % 60) }}
            </dd>
        </div>
        <div class="bg-white shadow sm:rounded-lg p-4">
            <dt class="text-sm font-medium text-gray-500">Total Cost</dt>
            <dd class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format($stats['total_cost'], 2) }}</dd>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="mb-6 bg-white shadow sm:rounded-lg p-4">
        <form method="GET" action="{{ route('reseller.cdr.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="date_from" class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="user_id" class="block text-xs font-medium text-gray-500 mb-1">User</label>
                    <select id="user_id" name="user_id"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->id === auth()->id() ? 'You' : $user->name }} ({{ ucfirst($user->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="disposition" class="block text-xs font-medium text-gray-500 mb-1">Disposition</label>
                    <select id="disposition" name="disposition"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All</option>
                        @foreach (['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED', 'CANCEL'] as $d)
                            <option value="{{ $d }}" {{ request('disposition') === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Caller / Callee</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                           placeholder="Number prefix..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Filter
                </button>
                <a href="{{ route('reseller.cdr.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                <a href="{{ route('reseller.cdr.export', request()->query()) }}"
                   class="ml-auto rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Export CSV
                </a>
            </div>
        </form>
    </div>

    {{-- Results Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date / Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caller</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Callee</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Billsec</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disposition</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                            {{ $record->call_start?->format('M d H:i:s') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-900">
                            {{ $record->caller }}
                            @if ($record->user)
                                <span class="block text-xs text-gray-500">{{ $record->user->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $record->callee }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">
                            {{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">
                            {{ sprintf('%d:%02d', intdiv($record->billsec, 60), $record->billsec % 60) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">
                            ${{ number_format($record->total_cost, 4) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @switch($record->disposition)
                                @case('ANSWERED')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">ANSWERED</span>
                                    @break
                                @case('NO ANSWER')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">NO ANSWER</span>
                                    @break
                                @case('BUSY')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">BUSY</span>
                                    @break
                                @case('FAILED')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">FAILED</span>
                                    @break
                                @case('CANCEL')
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">CANCEL</span>
                                    @break
                                @default
                                    <span class="text-gray-400">—</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('reseller.cdr.show', ['uuid' => $record->uuid, 'date' => $record->call_start?->format('Y-m-d')]) }}"
                               class="text-indigo-600 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                            No call records found for this date range.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($records->hasPages())
        <div class="mt-4">
            {{ $records->withQueryString()->links() }}
        </div>
    @endif
</x-reseller-layout>
