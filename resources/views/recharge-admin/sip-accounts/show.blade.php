<x-recharge-admin-layout>
    <x-slot name="header">SIP Account Details</x-slot>

    <div class="mb-6">
        <a href="{{ route('recharge-admin.sip-accounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to SIP Accounts
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- SIP Account Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">SIP Account Information</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Username</dt>
                        <dd class="font-medium text-gray-900">{{ $sipAccount->username }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd>
                            <span class="badge {{ $sipAccount->status === 'active' ? 'badge-success' : ($sipAccount->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                                {{ ucfirst($sipAccount->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Auth Mode</dt>
                        <dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $sipAccount->auth_mode)) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Caller ID Name</dt>
                        <dd class="text-gray-700">{{ $sipAccount->caller_id_name ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Caller ID Number</dt>
                        <dd class="text-gray-700">{{ $sipAccount->caller_id_number ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Max Channels</dt>
                        <dd class="font-medium">{{ $sipAccount->max_channels ?? 'Unlimited' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Created</dt>
                        <dd class="text-sm text-gray-700">{{ $sipAccount->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Owner Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">Owner Information</h3>
            </div>
            <div class="card-body">
                @if($sipAccount->user)
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                            <span class="text-lg font-medium text-gray-600">{{ substr($sipAccount->user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <a href="{{ route('recharge-admin.users.show', $sipAccount->user) }}" class="font-medium text-gray-900 hover:text-amber-600">
                                {{ $sipAccount->user->name }}
                            </a>
                            <p class="text-sm text-gray-500">{{ $sipAccount->user->email }}</p>
                        </div>
                    </div>

                    <dl class="space-y-3 pt-4 border-t border-gray-100">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Role</dt>
                            <dd>
                                <span class="badge {{ $sipAccount->user->role === 'reseller' ? 'badge-info' : 'badge-gray' }}">
                                    {{ ucfirst($sipAccount->user->role) }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Balance</dt>
                            <dd class="font-medium {{ $sipAccount->user->balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($sipAccount->user->balance, 2) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('recharge-admin.balance.create', ['user_id' => $sipAccount->user->id]) }}" class="btn-primary w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Recharge Balance
                        </a>
                    </div>
                @else
                    <p class="text-gray-500">Owner information not available</p>
                @endif
            </div>
        </div>
    </div>
</x-recharge-admin-layout>
