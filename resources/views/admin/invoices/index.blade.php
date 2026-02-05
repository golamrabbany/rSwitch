<x-admin-layout>
    <x-slot name="header">Invoices</x-slot>

    {{-- Filters --}}
    <div class="mb-6 bg-white shadow sm:rounded-lg p-4">
        <form method="GET" action="{{ route('admin.invoices.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
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
                        @foreach (['draft', 'issued', 'paid', 'overdue', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
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
                <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                <a href="{{ route('admin.invoices.create') }}" class="ml-auto rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">Create Invoice</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <a href="{{ route('admin.users.show', $invoice->user_id) }}" class="text-indigo-600 hover:text-indigo-500">{{ $invoice->user?->name ?? '—' }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                            {{ $invoice->period_start?->format('M d') }} - {{ $invoice->period_end?->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">${{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php $colors = ['draft' => 'bg-gray-100 text-gray-800', 'issued' => 'bg-blue-100 text-blue-800', 'paid' => 'bg-green-100 text-green-800', 'overdue' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-gray-100 text-gray-500']; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors[$invoice->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $invoice->due_date?->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($invoices->hasPages())
        <div class="mt-4">{{ $invoices->withQueryString()->links() }}</div>
    @endif
</x-admin-layout>
