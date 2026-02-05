<x-client-layout>
    <x-slot name="header">Payment Received</x-slot>

    <div class="max-w-lg">
        <div class="bg-white shadow sm:rounded-lg p-6 text-center space-y-4">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h3 class="text-lg font-semibold text-gray-900">Payment Successful</h3>

            @if ($payment)
                <p class="text-sm text-gray-500">
                    Your payment of <span class="font-semibold text-gray-900">${{ number_format($payment->amount, 2) }}</span>
                    has been received.
                </p>

                @if ($payment->status === 'completed')
                    <p class="text-sm text-green-600">Your balance has been credited.</p>
                @else
                    <p class="text-sm text-yellow-600">Your balance will be credited shortly.</p>
                @endif
            @else
                <p class="text-sm text-gray-500">Your payment is being processed. Your balance will be updated shortly.</p>
            @endif

            <div class="pt-4 flex justify-center gap-3">
                <a href="{{ route('client.dashboard') }}"
                   class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Go to Dashboard
                </a>
                <a href="{{ route('client.transactions.index') }}"
                   class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    View Transactions
                </a>
            </div>
        </div>
    </div>
</x-client-layout>
