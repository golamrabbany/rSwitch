<x-admin-layout>
    <x-slot name="header">Transaction #{{ $transaction->id }}</x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Transaction Details</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transaction->id }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">User</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if($transaction->user)
                                <a href="{{ route('admin.users.show', $transaction->user) }}" class="text-indigo-600 hover:text-indigo-500">{{ $transaction->user->name }}</a>
                                <span class="text-gray-500">({{ $transaction->user->email }})</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ in_array($transaction->type, ['topup', 'refund']) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                            </span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-sm font-semibold sm:col-span-2 sm:mt-0 {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->amount >= 0 ? '+' : '' }}${{ number_format($transaction->amount, 4) }}
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Balance After</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">${{ number_format($transaction->balance_after, 4) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transaction->description ?: '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Date</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transaction->created_at->format('M d, Y H:i:s') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Reference & Audit</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Reference Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transaction->reference_type ? ucfirst(str_replace('_', ' ', $transaction->reference_type)) : '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Reference ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $transaction->reference_id ?? '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Created By</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if($transaction->creator)
                                <a href="{{ route('admin.users.show', $transaction->creator) }}" class="text-indigo-600 hover:text-indigo-500">{{ $transaction->creator->name }}</a>
                                <span class="text-gray-500">({{ $transaction->creator->role }})</span>
                            @else
                                <span class="text-gray-400">System</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">&larr; Back to Transactions</a>
    </div>
</x-admin-layout>
