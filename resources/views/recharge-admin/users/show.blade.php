<x-recharge-admin-layout>
    <x-slot name="header">User Details</x-slot>

    <div class="mb-6">
        <a href="{{ route('recharge-admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Users
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Info Card -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                <span class="text-lg font-medium text-gray-600">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                        <a href="{{ route('recharge-admin.balance.create', ['user_id' => $user->id]) }}" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Recharge Balance
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">Role</dt>
                            <dd>
                                <span class="badge {{ $user->role === 'reseller' ? 'badge-info' : 'badge-gray' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd>
                                <span class="badge {{ $user->status === 'active' ? 'badge-success' : ($user->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Billing Type</dt>
                            <dd class="font-medium">{{ ucfirst($user->billing_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">KYC Status</dt>
                            <dd>
                                <span class="badge {{ $user->kyc_status === 'approved' ? 'badge-success' : ($user->kyc_status === 'pending' ? 'badge-warning' : 'badge-gray') }}">
                                    {{ ucfirst($user->kyc_status ?? 'N/A') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Created</dt>
                            <dd class="text-sm text-gray-700">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Credit Limit</dt>
                            <dd class="font-medium">${{ number_format($user->credit_limit, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Balance Card -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold text-gray-900">Balance</h3>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <p class="text-3xl font-bold {{ $user->balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($user->balance, 2) }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Current Balance</p>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Credit Limit</span>
                            <span class="font-medium">${{ number_format($user->credit_limit, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Available</span>
                            <span class="font-medium text-green-600">${{ number_format($user->balance + $user->credit_limit, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $transaction)
                        <tr>
                            <td class="text-sm text-gray-500">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $transaction->type === 'topup' ? 'badge-success' : 'badge-gray' }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td class="font-medium {{ floatval($transaction->amount) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ floatval($transaction->amount) >= 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 2) }}
                            </td>
                            <td class="text-sm text-gray-500">${{ number_format($transaction->balance_after, 2) }}</td>
                            <td class="text-sm text-gray-500 max-w-xs truncate">{{ $transaction->description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No transactions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-recharge-admin-layout>
