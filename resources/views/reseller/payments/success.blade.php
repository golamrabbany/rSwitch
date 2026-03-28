<x-reseller-layout>
    <x-slot name="header">Payment Successful</x-slot>

    <div class="max-w-lg mx-auto py-12 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful</h2>

        @if($payment)
            <p class="text-lg text-gray-600 mb-1">{{ format_currency($payment->amount) }}</p>
            <p class="text-sm text-gray-500 mb-6">
                @if($payment->status === 'completed')
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Credited to your account</span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Processing — balance will be credited shortly</span>
                @endif
            </p>
        @else
            <p class="text-gray-500 mb-6">Your payment is being processed.</p>
        @endif

        <div class="flex items-center justify-center gap-3">
            <a href="{{ route('reseller.dashboard') }}" class="btn-action-secondary">Dashboard</a>
            <a href="{{ route('reseller.transactions.index') }}" class="btn-primary-reseller">View Transactions</a>
        </div>
    </div>
</x-reseller-layout>
