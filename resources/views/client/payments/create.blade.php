<x-client-layout>
    <x-slot name="header">Add Funds</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Add Funds</h2>
            <p class="page-subtitle">Top up your account balance via secure payment</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Payment Form (2/3) --}}
        <div class="lg:col-span-2">
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-start justify-between w-full">
                        <div>
                            <h3 class="detail-card-title">Payment Details</h3>
                            <p class="text-sm text-gray-500 mt-1">Enter the amount you want to add to your balance</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                </div>
                <div class="p-6">
                    @if(session('error'))
                        <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm text-red-700">{{ session('error') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('client.payments.checkout') }}" class="space-y-6">
                        @csrf

                        {{-- Amount Input --}}
                        <div>
                            <label class="form-label">Top-Up Amount</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <span class="text-gray-500 font-medium">{{ currency_symbol() }}</span>
                                </div>
                                <input type="number" id="amount" name="amount" value="{{ old('amount', '25') }}" required
                                       min="5" max="10000" step="0.01"
                                       class="form-input text-xl font-semibold text-gray-900" style="padding-left: 2.5rem;">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Minimum {{ currency_symbol() }}5.00 — Maximum {{ currency_symbol() }}10,000.00</p>
                            @error('amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Quick Amount Buttons --}}
                        <div>
                            <label class="form-label">Quick Select</label>
                            <div class="flex gap-3">
                                @foreach ([10, 25, 50, 100] as $preset)
                                    <button type="button" onclick="document.getElementById('amount').value = {{ $preset }}"
                                            class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 transition-all text-center">
                                        {{ currency_symbol() }}{{ $preset }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="pt-2">
                            <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 text-base font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Pay with Stripe
                            </button>
                            <p class="text-xs text-center text-gray-400 mt-3">
                                <svg class="w-3.5 h-3.5 inline mr-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Secure payment processed by Stripe. You will be redirected to checkout.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Current Balance --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Balance</h3>
                </div>
                <div class="detail-card-body text-center py-6">
                    <div class="w-14 h-14 mx-auto rounded-full bg-indigo-100 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">{{ format_currency(auth()->user()->balance) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Current Balance</p>
                    @if(auth()->user()->billing_type === 'prepaid')
                        <span class="badge badge-blue mt-2">Prepaid</span>
                    @else
                        <span class="badge badge-purple mt-2">Postpaid</span>
                    @endif
                </div>
            </div>

            {{-- Payment Info --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">How it works</p>
                        <ul class="text-sm text-blue-600 mt-1 space-y-1">
                            <li>1. Enter your desired amount</li>
                            <li>2. Click "Pay with Stripe"</li>
                            <li>3. Complete payment on Stripe</li>
                            <li>4. Balance is credited instantly</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-client-layout>
