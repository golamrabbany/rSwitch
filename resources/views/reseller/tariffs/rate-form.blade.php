<x-reseller-layout>
    <x-slot name="header">{{ $rate ? 'Edit Rate' : 'Add Rate' }}</x-slot>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($rate)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    @endif
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $rate ? 'Edit Rate' : 'Add Rate' }}</h2>
                <p class="text-sm text-gray-500">{{ $rate ? 'Update rate for: ' : 'Add a new rate to: ' }}{{ $tariff->name }}</p>
            </div>
        </div>
        <a href="{{ route('reseller.tariffs.show', $tariff) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Rate Group
        </a>
    </div>

    <form method="POST" action="{{ $rate ? route('reseller.tariffs.update-rate', [$tariff, $rate]) : route('reseller.tariffs.add-rate', $tariff) }}">
        @csrf
        @if($rate) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Destination --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Destination</h3>
                        <p class="text-sm text-gray-500">Define the destination prefix and name</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="prefix" class="block text-sm font-medium text-gray-700 mb-1.5">Prefix</label>
                                <input type="text" name="prefix" id="prefix" required value="{{ old('prefix', $rate?->prefix) }}" placeholder="e.g. 1201" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-mono">
                                <p class="text-xs text-gray-400 mt-1.5">Numeric prefix for destination matching (1-20 digits)</p>
                                @error('prefix') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="destination" class="block text-sm font-medium text-gray-700 mb-1.5">Destination Name</label>
                                <input type="text" name="destination" id="destination" required value="{{ old('destination', $rate?->destination) }}" placeholder="e.g. United States - New Jersey" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <p class="text-xs text-gray-400 mt-1.5">Human-readable destination description</p>
                                @error('destination') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Pricing</h3>
                        <p class="text-sm text-gray-500">Configure rate and billing parameters</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="rate_per_minute" class="block text-sm font-medium text-gray-700 mb-1.5">Rate per Minute ($)</label>
                                <input type="number" name="rate_per_minute" id="rate_per_minute" required step="0.000001" min="0" value="{{ old('rate_per_minute', $rate ? number_format($rate->rate_per_minute, 6, '.', '') : '') }}" placeholder="0.015000" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-mono">
                                <p class="text-xs text-gray-400 mt-1.5">Up to 6 decimal places</p>
                                @error('rate_per_minute') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="connection_fee" class="block text-sm font-medium text-gray-700 mb-1.5">Connection Fee ($)</label>
                                <input type="number" name="connection_fee" id="connection_fee" step="0.000001" min="0" value="{{ old('connection_fee', $rate ? number_format($rate->connection_fee, 6, '.', '') : '0') }}" placeholder="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-mono">
                                <p class="text-xs text-gray-400 mt-1.5">One-time fee per call (optional)</p>
                            </div>
                            <div>
                                <label for="min_duration" class="block text-sm font-medium text-gray-700 mb-1.5">Minimum Duration (seconds)</label>
                                <input type="number" name="min_duration" id="min_duration" min="0" value="{{ old('min_duration', $rate?->min_duration ?? 0) }}" placeholder="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <p class="text-xs text-gray-400 mt-1.5">Minimum billable seconds (0 = no minimum)</p>
                            </div>
                            <div>
                                <label for="billing_increment" class="block text-sm font-medium text-gray-700 mb-1.5">Billing Increment (seconds)</label>
                                <input type="number" name="billing_increment" id="billing_increment" min="1" value="{{ old('billing_increment', $rate?->billing_increment ?? 6) }}" placeholder="6" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <p class="text-xs text-gray-400 mt-1.5">Common: 1 (per-second), 6 (6/6), 60 (per-minute)</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.tariffs.show', $tariff) }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 shadow-sm">
                        {{ $rate ? 'Update Rate' : 'Add Rate' }}
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Rate Group Info --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Rate Group</h3>
                    </div>
                    <div class="p-5">
                        <div class="bg-emerald-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-700">{{ $tariff->name }}</p>
                                    <p class="text-xs text-emerald-600">Reseller Rate Group</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Current Rates</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $tariff->rates()->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Assigned Clients</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $tariff->users()->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Type</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Reseller</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing Increments Info --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Billing Increments</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700">1 second</span>
                            <p class="text-sm text-gray-500 mt-1">Per-second billing. Most accurate, customer-friendly.</p>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700">6 seconds</span>
                            <p class="text-sm text-gray-500 mt-1">6/6 billing. Industry standard for wholesale.</p>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700">60 seconds</span>
                            <p class="text-sm text-gray-500 mt-1">Per-minute billing. Maximum revenue per call.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
