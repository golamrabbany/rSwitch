<x-admin-layout>
    <x-slot name="header">Call Detail: {{ Str::limit($record->uuid, 12) }}</x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Card 1: Call Information --}}
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
                        <dt class="text-sm font-medium text-gray-500">Call Flow</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                {{ str_replace('_', ' ', strtoupper($record->call_flow ?? '—')) }}
                            </span>
                        </dd>
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
                        <dt class="text-sm font-medium text-gray-500">Matched Prefix</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0">{{ $record->matched_prefix ?: '—' }}</dd>
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
                                @case('CANCEL')
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">CANCEL</span>
                                    @break
                                @default
                                    <span class="text-gray-400">—</span>
                            @endswitch
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Hangup Cause</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $record->hangup_cause ?? '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @switch($record->status)
                                @case('rated')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Rated</span>
                                    @break
                                @case('in_progress')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">In Progress</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Failed</span>
                                    @break
                                @case('unbillable')
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">Unbillable</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Card 2: Timing & Duration --}}
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
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Billable Duration</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ sprintf('%d:%02d', intdiv($record->billable_duration, 60), $record->billable_duration % 60) }}
                            <span class="text-gray-500">({{ $record->billable_duration }}s)</span>
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Card 3: Billing --}}
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
                        <dt class="text-sm font-medium text-gray-500">Total Cost</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($record->total_cost, 4) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Reseller Cost</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($record->reseller_cost, 4) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Rated At</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $record->rated_at?->format('Y-m-d H:i:s') ?? 'Not rated' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Card 4: Routing & Network --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Routing & Network</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">User</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if ($record->user)
                                <a href="{{ route('admin.users.show', $record->user) }}" class="text-indigo-600 hover:text-indigo-500">{{ $record->user->name }}</a>
                                <span class="text-gray-500">({{ ucfirst($record->user->role) }})</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Reseller</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if ($record->reseller)
                                <a href="{{ route('admin.users.show', $record->reseller) }}" class="text-indigo-600 hover:text-indigo-500">{{ $record->reseller->name }}</a>
                            @else
                                <span class="text-gray-400">Direct / Admin</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">SIP Account</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if ($record->sipAccount)
                                <a href="{{ route('admin.sip-accounts.show', $record->sipAccount) }}" class="text-indigo-600 hover:text-indigo-500 font-mono">{{ $record->sipAccount->username }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Incoming Trunk</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if ($record->incomingTrunk)
                                <a href="{{ route('admin.trunks.show', $record->incomingTrunk) }}" class="text-indigo-600 hover:text-indigo-500">{{ $record->incomingTrunk->name }}</a>
                                <span class="text-gray-500">({{ $record->incomingTrunk->provider }})</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Outgoing Trunk</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if ($record->outgoingTrunk)
                                <a href="{{ route('admin.trunks.show', $record->outgoingTrunk) }}" class="text-indigo-600 hover:text-indigo-500">{{ $record->outgoingTrunk->name }}</a>
                                <span class="text-gray-500">({{ $record->outgoingTrunk->provider }})</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">DID</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if ($record->did)
                                <a href="{{ route('admin.dids.show', $record->did) }}" class="text-indigo-600 hover:text-indigo-500 font-mono">{{ $record->did->number }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Source IP</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0">{{ $record->src_ip ?? '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Destination IP</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0">{{ $record->dst_ip ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Card 5: Asterisk Technical Details (full-width) --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Asterisk Technical Details</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-6 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Channel</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-5 sm:mt-0">{{ $record->ast_channel ?? '—' }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-6 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Dst Channel</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-5 sm:mt-0">{{ $record->ast_dstchannel ?? '—' }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-6 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">Context</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-5 sm:mt-0">{{ $record->ast_context ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <a href="{{ route('admin.cdr.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            &larr; Back to CDR List
        </a>
    </div>
</x-admin-layout>
