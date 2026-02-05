<x-admin-layout>
    <x-slot name="header">Payments</x-slot>

    {{-- Filters --}}
    <div class="mb-6 bg-white shadow sm:rounded-lg p-4">
        <form method="GET" action="{{ route('admin.payments.index') }}">
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
                    <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select id="status" name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        @foreach (['pending', 'completed', 'failed', 'refunded'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="payment_method" class="block text-xs font-medium text-gray-500 mb-1">Method</label>
                    <select id="payment_method" name="payment_method" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Methods</option>
                        @foreach (['manual_admin', 'manual_reseller', 'stripe', 'paypal', 'bank_transfer'] as $m)
                            <option value="{{ $m }}" {{ request('payment_method') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
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
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Filter</button>
                <a href="{{ route('admin.payments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
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
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <a href="{{ route('admin.users.show', $payment->user_id) }}" class="text-indigo-600 hover:text-indigo-500">{{ $payment->user?->name ?? '—' }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">${{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($payment->invoice)
                                <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="text-indigo-600 hover:text-indigo-500">{{ $payment->invoice->invoice_number }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-indigo-600 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payments->hasPages())
        <div class="mt-4">{{ $payments->withQueryString()->links() }}</div>
    @endif
</x-admin-layout>
