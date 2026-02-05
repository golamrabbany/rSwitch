<x-client-layout>
    <x-slot name="header">Call Detail: {{ Str::limit($record->uuid, 12) }}</x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Call Information</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">UUID</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0 break-all">{{ $record->uuid }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Caller</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0">{{ $record->caller }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Callee</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0">{{ $record->callee }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Caller ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $record->caller_id ?? '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Destination</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $record->destination ?: '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Disposition</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
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
                                @default
                                    <span class="text-gray-400">{{ $record->disposition ?? '—' }}</span>
                            @endswitch
                        </dd>
                    </div>
                    @if($record->sipAccount)
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">SIP Account</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <a href="{{ route('client.sip-accounts.show', $record->sipAccount) }}" class="text-indigo-600 hover:text-indigo-500 font-mono">{{ $record->sipAccount->username }}</a>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="space-y-6">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Timing & Duration</h3>
                    </div>
                    <dl class="divide-y divide-gray-200">
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Call Start</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $record->call_start?->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Call End</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $record->call_end?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Duration</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                {{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}
                                <span class="text-gray-500">({{ $record->duration }}s)</span>
                            </dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Billsec</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                {{ sprintf('%d:%02d', intdiv($record->billsec, 60), $record->billsec % 60) }}
                                <span class="text-gray-500">({{ $record->billsec }}s)</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Billing</h3>
                    </div>
                    <dl class="divide-y divide-gray-200">
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Rate / Minute</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($record->rate_per_minute, 6) }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Connection Fee</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($record->connection_fee, 6) }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Cost</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($record->total_cost, 4) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <a href="{{ route('client.cdr.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">&larr; Back to CDR List</a>
    </div>
</x-client-layout>
