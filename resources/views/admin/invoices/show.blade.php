<x-admin-layout>
    <x-slot name="header">Invoice: {{ $invoice->invoice_number }}</x-slot>

    <div class="space-y-6">
        {{-- Status Actions --}}
        <div class="flex items-center gap-3">
            @if($invoice->status === 'draft')
                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="action" value="issue">
                    <button type="submit" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"
                            onclick="return confirm('Issue this invoice?')">Issue Invoice</button>
                </form>
            @endif

            @if(in_array($invoice->status, ['issued', 'overdue']))
                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="action" value="mark_paid">
                    <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                            onclick="return confirm('Mark this invoice as paid?')">Mark Paid</button>
                </form>
            @endif

            @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                            onclick="return confirm('Cancel this invoice?')">Cancel</button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Invoice Details</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Invoice Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">User</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <a href="{{ route('admin.users.show', $invoice->user) }}" class="text-indigo-600 hover:text-indigo-500">{{ $invoice->user->name }}</a>
                            <span class="text-gray-500">({{ $invoice->user->email }})</span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Period</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $invoice->period_start?->format('M d, Y') }} - {{ $invoice->period_end?->format('M d, Y') }}
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 sm:col-span-2 sm:mt-0">
                            @php $colors = ['draft' => 'bg-gray-100 text-gray-800', 'issued' => 'bg-blue-100 text-blue-800', 'paid' => 'bg-green-100 text-green-800', 'overdue' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-gray-100 text-gray-500']; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors[$invoice->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $invoice->due_date?->format('M d, Y') }}</dd>
                    </div>
                    @if($invoice->paid_at)
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Paid At</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $invoice->paid_at->format('M d, Y H:i') }}</dd>
                    </div>
                    @endif
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $invoice->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Charges</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Call Charges</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($invoice->call_charges, 2) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">DID Charges</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($invoice->did_charges, 2) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tax</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($invoice->tax_amount, 2) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50">
                        <dt class="text-sm font-semibold text-gray-900">Total Amount</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($invoice->total_amount, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Payments --}}
        @if($invoice->payments->count() > 0)
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Payments</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 text-right">${{ number_format($payment->amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-indigo-600 hover:text-indigo-500">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">&larr; Back to Invoices</a>
    </div>
</x-admin-layout>
