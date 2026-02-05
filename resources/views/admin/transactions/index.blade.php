<x-admin-layout>
    <x-slot name="header">Transactions</x-slot>

    {{-- Filters --}}
    <div class="mb-6 bg-white shadow sm:rounded-lg p-4">
        <form method="GET" action="{{ route('admin.transactions.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label for="user_id" class="block text-xs font-medium text-gray-500 mb-1">User</label>
                    <select id="user_id" name="user_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="type" class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                    <select id="type" name="type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Types</option>
                        @foreach (['topup', 'call_charge', 'did_charge', 'refund', 'adjustment', 'invoice_payment'] as $t)
                            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Search..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Filter</button>
                <a href="{{ route('admin.transactions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                <a href="{{ route('admin.balance.create') }}" class="ml-auto rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Adjust Balance</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance After</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($transactions as $txn)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $txn->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <a href="{{ route('admin.users.show', $txn->user_id) }}" class="text-indigo-600 hover:text-indigo-500">{{ $txn->user?->name ?? '—' }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ in_array($txn->type, ['topup', 'refund']) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $txn->type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ Str::limit($txn->description, 50) }}</td>
                        <td class="px-4 py-3 text-sm text-right tabular-nums font-medium {{ $txn->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $txn->amount >= 0 ? '+' : '' }}${{ number_format($txn->amount, 4) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right tabular-nums text-gray-900">${{ number_format($txn->balance_after, 4) }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('admin.transactions.show', $txn) }}" class="text-indigo-600 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transactions->hasPages())
        <div class="mt-4">{{ $transactions->withQueryString()->links() }}</div>
    @endif
</x-admin-layout>
