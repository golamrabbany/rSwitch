<x-client-layout>
    <x-slot name="header">Add Funds</x-slot>

    <div class="max-w-lg">
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Current Balance</h3>
                <p class="mt-1 text-3xl font-bold text-gray-900">${{ number_format(auth()->user()->balance, 2) }}</p>
                <p class="text-sm text-gray-500">{{ auth()->user()->currency ?? 'USD' }}</p>
            </div>

            <hr class="border-gray-200">

            <form method="POST" action="{{ route('client.payments.checkout') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Top-Up Amount</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">$</span>
                        </div>
                        <input type="number" id="amount" name="amount" value="{{ old('amount', '25') }}" required
                               min="5" max="10000" step="0.01"
                               class="block w-full rounded-md border-gray-300 pl-7 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Minimum $5.00, maximum $10,000.00</p>
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>

                <div class="flex gap-2">
                    @foreach ([10, 25, 50, 100] as $preset)
                        <button type="button" onclick="document.getElementById('amount').value = {{ $preset }}"
                                class="flex-1 rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                            ${{ $preset }}
                        </button>
                    @endforeach
                </div>

                <button type="submit"
                        class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Pay with Stripe
                </button>

                <p class="text-xs text-center text-gray-400">
                    Secure payment processed by Stripe. You will be redirected to Stripe's checkout page.
                </p>
            </form>
        </div>
    </div>
</x-client-layout>
