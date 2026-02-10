<x-recharge-admin-layout>
    <x-slot name="header">Transaction Details</x-slot>

    <div class="mb-6">
        <a href="{{ route('recharge-admin.transactions.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Transactions
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Transaction Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold text-gray-900">Transaction Information</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Transaction ID</dt>
                        <dd class="font-mono text-sm text-gray-900">#{{ $transaction->id }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Type</dt>
                        <dd>
                            <span class="badge {{ $transaction->type === 'topup' ? 'badge-success' : ($transaction->type === 'call_charge' ? 'badge-info' : 'badge-warning') }}">
                                {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Amount</dt>
                        <dd class="font-bold text-lg {{ floatval($transaction->amount) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ floatval($transaction->amount) >= 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 4) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Balance After</dt>
                        <dd class="font-medium">${{ number_format($transaction->balance_after, 4) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Date</dt>
                        <dd class="text-gray-700">{{ $transaction->created_at->format('M d, Y H:i:s') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Source</dt>
                        <dd class="text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->source ?? 'N/A')) }}</dd>
                    </div>
                    @if($transaction->description)
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">Description</dt>
                            <dd class="text-gray-700 text-sm">{{ $transaction->description }}</dd>
                        </div>
                    @endif
                    @if($transaction->remarks)
                        <div>
                            <dt class="text-sm text-gray-500 mb-1">Remarks / Reason</dt>
                            <dd class="text-gray-700 text-sm bg-gray-50 p-3 rounded-lg">{{ $transaction->remarks }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- User & Creator Info -->
        <div class="space-y-6">
            <!-- User Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold text-gray-900">Account Holder</h3>
                </div>
                <div class="card-body">
                    @if($transaction->user)
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                <span class="text-lg font-medium text-gray-600">{{ substr($transaction->user->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <a href="{{ route('recharge-admin.users.show', $transaction->user) }}" class="font-medium text-gray-900 hover:text-amber-600">
                                    {{ $transaction->user->name }}
                                </a>
                                <p class="text-sm text-gray-500">{{ $transaction->user->email }}</p>
                            </div>
                        </div>

                        <dl class="space-y-2 pt-4 border-t border-gray-100">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Role</dt>
                                <dd>
                                    <span class="badge {{ $transaction->user->role === 'reseller' ? 'badge-info' : 'badge-gray' }}">
                                        {{ ucfirst($transaction->user->role) }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Current Balance</dt>
                                <dd class="font-medium {{ $transaction->user->balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($transaction->user->balance, 2) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <a href="{{ route('recharge-admin.balance.create', ['user_id' => $transaction->user->id]) }}" class="btn-primary w-full justify-center">
                                Recharge Balance
                            </a>
                        </div>
                    @else
                        <p class="text-gray-500">User information not available</p>
                    @endif
                </div>
            </div>

            <!-- Creator Info -->
            @if($transaction->creator)
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-semibold text-gray-900">Created By</h3>
                    </div>
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center mr-3">
                                <span class="text-sm font-medium text-amber-700">{{ substr($transaction->creator->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $transaction->creator->name }}
                                    @if($transaction->created_by === auth()->id())
                                        <span class="text-amber-600">(You)</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">{{ $transaction->creator->email }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-recharge-admin-layout>
