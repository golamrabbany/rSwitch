<x-recharge-admin-layout>
    <x-slot name="header">DID Details</x-slot>

    <div class="mb-6">
        <a href="{{ route('recharge-admin.dids.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to DIDs
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- DID Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">DID Information</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">DID Number</dt>
                        <dd class="font-medium text-gray-900 text-lg">{{ $did->number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd>
                            <span class="badge {{ $did->status === 'active' ? 'badge-success' : 'badge-gray' }}">
                                {{ ucfirst($did->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Trunk</dt>
                        <dd class="text-gray-700">{{ $did->trunk?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Destination Type</dt>
                        <dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $did->destination_type ?? 'N/A')) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Monthly Cost</dt>
                        <dd class="font-medium">${{ number_format($did->monthly_cost ?? 0, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Monthly Price</dt>
                        <dd class="font-medium">${{ number_format($did->monthly_price ?? 0, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Owner Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">Assigned To</h3>
            </div>
            <div class="card-body">
                @if($did->assignedUser)
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                            <span class="text-lg font-medium text-gray-600">{{ substr($did->assignedUser->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <a href="{{ route('recharge-admin.users.show', $did->assignedUser) }}" class="font-medium text-gray-900 hover:text-amber-600">
                                {{ $did->assignedUser->name }}
                            </a>
                            <p class="text-sm text-gray-500">{{ $did->assignedUser->email }}</p>
                        </div>
                    </div>

                    <dl class="space-y-3 pt-4 border-t border-gray-100">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Role</dt>
                            <dd>
                                <span class="badge {{ $did->assignedUser->role === 'reseller' ? 'badge-info' : 'badge-gray' }}">
                                    {{ ucfirst($did->assignedUser->role) }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Balance</dt>
                            <dd class="font-medium {{ $did->assignedUser->balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($did->assignedUser->balance, 2) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <a href="{{ route('recharge-admin.balance.create', ['user_id' => $did->assignedUser->id]) }}" class="btn-primary w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Recharge Balance
                        </a>
                    </div>
                @else
                    <p class="text-gray-500">This DID is not assigned to any user</p>
                @endif
            </div>
        </div>
    </div>
</x-recharge-admin-layout>
