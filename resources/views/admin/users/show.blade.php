<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>User: {{ $user->name }}</span>
            <div class="flex items-center gap-x-3">
                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Edit</a>
                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                    @csrf
                    <button type="submit" class="rounded-md {{ $user->status === 'active' ? 'bg-yellow-600 hover:bg-yellow-500' : 'bg-green-600 hover:bg-green-500' }} px-3 py-1.5 text-sm font-semibold text-white shadow-sm">
                        {{ $user->status === 'active' ? 'Suspend' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Account Info --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Account Information</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->name }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->email }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Role</dt>
                    <dd class="mt-1 sm:col-span-2 sm:mt-0">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : ($user->role === 'reseller' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 sm:col-span-2 sm:mt-0">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : ($user->status === 'suspended' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Parent</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        @if($user->parent)
                            <a href="{{ route('admin.users.show', $user->parent) }}" class="text-indigo-600 hover:text-indigo-900">{{ $user->parent->name }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">KYC Status</dt>
                    <dd class="mt-1 sm:col-span-2 sm:mt-0">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $user->kyc_status === 'approved' ? 'bg-green-100 text-green-800' : ($user->kyc_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($user->kyc_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                            {{ ucfirst($user->kyc_status) }}
                        </span>
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Billing Info --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Billing Information</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Billing Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst($user->billing_type) }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Balance</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($user->balance, 2) }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Credit Limit</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($user->credit_limit, 2) }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Max Channels</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->max_channels }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Rate Group</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->rateGroup?->name ?? 'None' }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Daily Spend Limit</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->daily_spend_limit ? '$' . number_format($user->daily_spend_limit, 2) : 'Unlimited' }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Daily Call Limit</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $user->daily_call_limit ?? 'Unlimited' }}</dd>
                </div>
            </dl>
        </div>

        {{-- SIP Accounts --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">SIP Accounts ({{ $user->sipAccounts->count() }})</h3>
            </div>
            @if($user->sipAccounts->isEmpty())
                <p class="px-6 py-4 text-sm text-gray-500">No SIP accounts yet.</p>
            @else
                <ul class="divide-y divide-gray-200">
                    @foreach($user->sipAccounts as $sip)
                        <li class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $sip->username }}</p>
                                <p class="text-xs text-gray-500">{{ $sip->context }} / {{ $sip->transport }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $sip->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($sip->status) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Children (sub-accounts) --}}
        @if($user->role === 'reseller')
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Clients ({{ $user->children->count() }})</h3>
                </div>
                @if($user->children->isEmpty())
                    <p class="px-6 py-4 text-sm text-gray-500">No clients yet.</p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach($user->children as $child)
                            <li class="px-6 py-3 flex items-center justify-between">
                                <div>
                                    <a href="{{ route('admin.users.show', $child) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">{{ $child->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $child->email }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $child->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($child->status) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
</x-admin-layout>
