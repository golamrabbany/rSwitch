<x-recharge-admin-layout>
    <x-slot name="header">Transactions</x-slot>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Transactions</h3>
                <p class="text-sm text-gray-500">View-only access to transactions under your assigned resellers</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <form action="{{ route('recharge-admin.transactions.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                    <select name="type" class="form-select text-sm">
                        <option value="">All Types</option>
                        <option value="topup" {{ request('type') === 'topup' ? 'selected' : '' }}>Topup</option>
                        <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        <option value="call_charge" {{ request('type') === 'call_charge' ? 'selected' : '' }}>Call Charge</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">User</label>
                    <select name="user_id" class="form-select text-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ ucfirst($user->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center">
                        <input type="checkbox" name="my_transactions" value="1" {{ request('my_transactions') ? 'checked' : '' }} class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                        <span class="ml-2 text-sm text-gray-600">My transactions only</span>
                    </label>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-secondary text-sm">Filter</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Created By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="text-sm text-gray-500">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if($transaction->user)
                                    <a href="{{ route('recharge-admin.users.show', $transaction->user) }}" class="text-amber-600 hover:underline">
                                        {{ $transaction->user->name }}
                                    </a>
                                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $transaction->user->role === 'reseller' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($transaction->user->role) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $transaction->type === 'topup' ? 'badge-success' : ($transaction->type === 'call_charge' ? 'badge-info' : 'badge-warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                </span>
                            </td>
                            <td class="font-medium {{ floatval($transaction->amount) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ floatval($transaction->amount) >= 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 2) }}
                            </td>
                            <td class="text-sm text-gray-500">${{ number_format($transaction->balance_after, 2) }}</td>
                            <td class="text-sm text-gray-500">
                                @if($transaction->creator)
                                    {{ $transaction->creator->name }}
                                    @if($transaction->created_by === auth()->id())
                                        <span class="text-amber-600">(You)</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">System</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('recharge-admin.transactions.show', $transaction) }}" class="btn-ghost text-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500">No transactions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</x-recharge-admin-layout>
