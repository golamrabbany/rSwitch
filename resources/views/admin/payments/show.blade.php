<x-admin-layout>
    <x-slot name="header">Payment #{{ $payment->id }}</x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Payment Details</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $payment->id }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">User</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if($payment->user)
                                <a href="{{ route('admin.users.show', $payment->user) }}" class="text-indigo-600 hover:text-indigo-500">{{ $payment->user->name }}</a>
                                <span class="text-gray-500">({{ $payment->user->email }})</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($payment->amount, 4) }} {{ $payment->currency }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Method</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $payment->notes ?: '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $payment->created_at->format('M d, Y H:i:s') }}</dd>
                    </div>
                    @if($payment->completed_at)
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Completed At</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $payment->completed_at->format('M d, Y H:i:s') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="space-y-6">
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">References</h3>
                    </div>
                    <dl class="divide-y divide-gray-200">
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Recharged By</dt>
                            <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                @if($payment->rechargedBy)
                                    <a href="{{ route('admin.users.show', $payment->rechargedBy) }}" class="text-indigo-600 hover:text-indigo-500">{{ $payment->rechargedBy->name }}</a>
                                    <span class="text-gray-500">({{ $payment->rechargedBy->role }})</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Invoice</dt>
                            <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                @if($payment->invoice)
                                    <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="text-indigo-600 hover:text-indigo-500">{{ $payment->invoice->invoice_number }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Transaction ID</dt>
                            <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                @if($payment->transaction)
                                    <a href="{{ route('admin.transactions.show', $payment->transaction) }}" class="text-indigo-600 hover:text-indigo-500">#{{ $payment->transaction->id }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Gateway Transaction ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono sm:col-span-2 sm:mt-0">{{ $payment->gateway_transaction_id ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>

                @if($payment->gateway_response)
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Gateway Response</h3>
                    </div>
                    <div class="p-4">
                        <pre class="text-xs text-gray-700 bg-gray-50 rounded-md p-4 overflow-x-auto">{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">&larr; Back to Payments</a>
    </div>
</x-admin-layout>
