<x-recharge-admin-layout>
    <x-slot name="header">Call Detail</x-slot>

    <div class="mb-6">
        <a href="{{ route('recharge-admin.cdr.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to CDR
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Call Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">Call Information</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Caller</dt>
                        <dd class="font-medium text-gray-900">{{ $call->caller }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Callee</dt>
                        <dd class="font-medium text-gray-900">{{ $call->callee }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Call Start</dt>
                        <dd class="text-gray-700">{{ $call->call_start->format('M d, Y H:i:s') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Call End</dt>
                        <dd class="text-gray-700">{{ $call->call_end?->format('M d, Y H:i:s') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Duration</dt>
                        <dd class="font-medium">{{ gmdate('H:i:s', $call->duration ?? 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Disposition</dt>
                        <dd>
                            <span class="badge {{ $call->disposition === 'ANSWERED' ? 'badge-success' : ($call->disposition === 'NO ANSWER' ? 'badge-warning' : 'badge-danger') }}">
                                {{ $call->disposition }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Billing Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">Billing Information</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Billable Duration</dt>
                        <dd class="font-medium">{{ $call->billable_duration ?? 0 }} seconds</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Rate per Minute</dt>
                        <dd class="font-medium">${{ number_format($call->rate_per_minute ?? 0, 4) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Cost</dt>
                        <dd class="font-bold text-lg text-amber-600">${{ number_format($call->total_cost ?? 0, 4) }}</dd>
                    </div>
                </dl>

                @if($call->user)
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Call Owner</h4>
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                <span class="text-sm font-medium text-gray-600">{{ substr($call->user->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <a href="{{ route('recharge-admin.users.show', $call->user) }}" class="font-medium text-gray-900 hover:text-amber-600">
                                    {{ $call->user->name }}
                                </a>
                                <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $call->user->role === 'reseller' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($call->user->role) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('recharge-admin.balance.create', ['user_id' => $call->user->id]) }}" class="btn-primary text-sm">
                                Recharge Balance
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-recharge-admin-layout>
