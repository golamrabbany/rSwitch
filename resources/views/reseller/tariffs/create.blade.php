<x-reseller-layout>
    <x-slot name="header">Create Rate Group</x-slot>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Create Rate Group</h2>
                <p class="text-sm text-gray-500">Add a new rate group for your clients</p>
            </div>
        </div>
        <a href="{{ route('reseller.tariffs.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to List
        </a>
    </div>

    <form method="POST" action="{{ route('reseller.tariffs.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Rate Group Details --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Rate Group Details</h3>
                        <p class="text-sm text-gray-500">Basic information about this rate group</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g., Premium Rates, Standard Plan" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <p class="text-xs text-gray-400 mt-1.5">A descriptive name for this rate group</p>
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                            <textarea name="description" id="description" rows="3" placeholder="Optional description for this rate group" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description') }}</textarea>
                            <p class="text-xs text-gray-400 mt-1.5">Optional notes about this rate group's purpose</p>
                        </div>
                    </div>
                </div>

                {{-- Rate Configuration --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Rate Configuration</h3>
                        <p class="text-sm text-gray-500">Configure initial rates for this rate group</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="bg-gray-50 rounded-lg p-4 flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Base Tariff: {{ $baseTariff->name }}</p>
                                <p class="text-sm text-gray-500">{{ $baseTariff->rates()->where('status', 'active')->count() }} active rates</p>
                                <p class="text-xs text-gray-400 mt-1">Your rate group inherits cost rates from this base. The difference between your rates and base rates is your profit margin.</p>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-5">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="copy_rates" value="1" checked class="w-4 h-4 mt-0.5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Copy rates from base tariff</span>
                                    <p class="text-xs text-gray-400 mt-0.5">Start with all base rates and adjust individual rates later</p>
                                </div>
                            </label>

                            <div class="mt-4 ml-7">
                                <label for="markup_percent" class="block text-sm font-medium text-gray-700 mb-1.5">Markup Percentage</label>
                                <div class="flex items-center gap-3">
                                    <input type="number" name="markup_percent" id="markup_percent" value="{{ old('markup_percent', 20) }}" min="0" max="500" step="1" class="w-28 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <span class="text-sm text-gray-500">% above base rates</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1.5">e.g., 20% markup: base rate $0.020/min becomes $0.024/min for your clients</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.tariffs.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 shadow-sm">Create Rate Group</button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Quick Info --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Quick Info</h3>
                    </div>
                    <div class="p-5">
                        <div class="bg-emerald-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-700">Rate Groups</p>
                                    <p class="text-xs text-emerald-600">Organize rates for billing</p>
                                </div>
                            </div>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-2.5 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Set rates per destination prefix
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Assign to clients for billing
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Profit = your rate - base rate
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- How it Works --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">How Billing Works</h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700">Base Tariff</span>
                            <p class="text-sm text-gray-500 mt-1">Your cost rate from admin. This is what you pay per call.</p>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Your Tariff</span>
                            <p class="text-sm text-gray-500 mt-1">Rates you set for clients. Should be higher than base for profit.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
