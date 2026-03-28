<x-reseller-layout>
    <x-slot name="header">Add Funds</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Add Funds</h2>
            <p class="page-subtitle">Top up your reseller account balance</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.payments.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Payment History
            </a>
        </div>
    </div>

    <div x-data="{ gateway: '{{ $gateways->first() }}' }" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Payment Form --}}
        <div class="lg:col-span-2">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Payment Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    @if(session('error'))
                        <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm text-red-700">{{ session('error') }}</span>
                        </div>
                    @endif

                    {{-- Amount --}}
                    <div>
                        <label class="form-label">Top-Up Amount</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <span class="text-gray-500 font-medium">{{ currency_symbol() }}</span>
                            </div>
                            <input type="number" id="amount" name="amount" value="{{ old('amount', '100') }}" required
                                   min="5" max="10000" step="0.01" form="payment-form"
                                   class="form-input text-xl font-semibold text-gray-900" style="padding-left: 2.5rem;">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Minimum {{ currency_symbol() }}5 — Maximum {{ currency_symbol() }}10,000</p>
                    </div>

                    {{-- Quick Amount --}}
                    <div>
                        <label class="form-label">Quick Select</label>
                        <div class="flex gap-3">
                            @foreach ([50, 100, 500, 1000] as $preset)
                                <button type="button" onclick="document.getElementById('amount').value = {{ $preset }}"
                                        class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-700 transition-all text-center">
                                    {{ currency_symbol() }}{{ number_format($preset) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    @if($gateways->count() > 1)
                    <div>
                        <label class="form-label">Payment Method</label>
                        <div class="grid grid-cols-{{ min($gateways->count(), 3) }} gap-3">
                            @if($gateways->contains('sslcommerz'))
                                <label class="relative cursor-pointer" @click="gateway = 'sslcommerz'">
                                    <div class="p-4 rounded-lg border-2 transition-all text-center"
                                         :class="gateway === 'sslcommerz' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="text-2xl mb-1">🏦</div>
                                        <p class="text-sm font-semibold text-gray-900">SSLCommerz</p>
                                        <p class="text-xs text-gray-500">Card / Mobile Banking</p>
                                    </div>
                                </label>
                            @endif
                            @if($gateways->contains('bkash'))
                                <label class="relative cursor-pointer" @click="gateway = 'bkash'">
                                    <div class="p-4 rounded-lg border-2 transition-all text-center"
                                         :class="gateway === 'bkash' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="text-2xl mb-1">📱</div>
                                        <p class="text-sm font-semibold text-gray-900">bKash</p>
                                        <p class="text-xs text-gray-500">Mobile Banking</p>
                                    </div>
                                </label>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Submit --}}
                    <div class="pt-2">
                        <form id="payment-form" method="POST"
                              :action="gateway === 'sslcommerz' ? '{{ route('reseller.payments.checkout-sslcommerz') }}' : '{{ route('reseller.payments.checkout-bkash') }}'">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 text-base font-semibold text-white rounded-lg transition-colors" style="background: #059669;">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span x-text="gateway === 'sslcommerz' ? 'Pay with SSLCommerz' : 'Pay with bKash'"></span>
                            </button>
                        </form>
                        <p class="text-xs text-center text-gray-400 mt-3">Secure payment. You will be redirected to complete checkout.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Balance</h3>
                </div>
                <div class="detail-card-body text-center py-6">
                    <p class="text-3xl font-bold {{ auth()->user()->balance > 0 ? 'text-emerald-600' : 'text-gray-900' }}">{{ format_currency(auth()->user()->balance) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Current Balance</p>
                    <span class="inline-flex items-center gap-1 text-xs font-medium mt-2 {{ auth()->user()->billing_type === 'prepaid' ? 'text-blue-700' : 'text-purple-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->billing_type === 'prepaid' ? 'bg-blue-500' : 'bg-purple-500' }}"></span>
                        {{ ucfirst(auth()->user()->billing_type) }}
                    </span>
                    @if(auth()->user()->credit_limit > 0)
                        <p class="text-xs text-gray-400 mt-1">Credit: {{ format_currency(auth()->user()->credit_limit) }}</p>
                    @endif
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">How it works</p>
                        <ul class="text-sm text-blue-600 mt-1 space-y-1">
                            <li>1. Enter your desired amount</li>
                            <li>2. Select payment method</li>
                            <li>3. Complete payment on gateway</li>
                            <li>4. Balance is credited instantly</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
