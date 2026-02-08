<x-admin-layout>
    <x-slot name="header">Edit Rate</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Rate</h2>
                <p class="page-subtitle">Update rate: {{ $rate->prefix }} — {{ $rate->destination }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.rate-groups.rates.show', [$rateGroup, $rate]) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Rate
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.rate-groups.rates.update', [$rateGroup, $rate]) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Destination --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Destination</h3>
                        <p class="form-card-subtitle">Define the destination prefix and name</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="prefix" class="form-label">Prefix</label>
                                <input type="text" id="prefix" name="prefix" value="{{ old('prefix', $rate->prefix) }}" required class="form-input form-input-mono">
                                <p class="form-hint">Numeric prefix for destination matching</p>
                                <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="destination" class="form-label">Destination Name</label>
                                <input type="text" id="destination" name="destination" value="{{ old('destination', $rate->destination) }}" required class="form-input">
                                <p class="form-hint">Human-readable destination description</p>
                                <x-input-error :messages="$errors->get('destination')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Pricing</h3>
                        <p class="form-card-subtitle">Configure rate and billing parameters</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="rate_per_minute" class="form-label">Rate per Minute ($)</label>
                                <input type="number" id="rate_per_minute" name="rate_per_minute" value="{{ old('rate_per_minute', $rate->rate_per_minute) }}" required step="0.000001" min="0" class="form-input form-input-mono">
                                <p class="form-hint">Up to 6 decimal places</p>
                                <x-input-error :messages="$errors->get('rate_per_minute')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="connection_fee" class="form-label">Connection Fee ($)</label>
                                <input type="number" id="connection_fee" name="connection_fee" value="{{ old('connection_fee', $rate->connection_fee) }}" step="0.000001" min="0" class="form-input form-input-mono">
                                <p class="form-hint">One-time fee per call (optional)</p>
                                <x-input-error :messages="$errors->get('connection_fee')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="min_duration" class="form-label">Minimum Duration (seconds)</label>
                                <input type="number" id="min_duration" name="min_duration" value="{{ old('min_duration', $rate->min_duration) }}" min="0" class="form-input">
                                <p class="form-hint">Minimum billable seconds (0 = no minimum)</p>
                                <x-input-error :messages="$errors->get('min_duration')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="billing_increment" class="form-label">Billing Increment (seconds)</label>
                                <input type="number" id="billing_increment" name="billing_increment" value="{{ old('billing_increment', $rate->billing_increment) }}" min="1" class="form-input">
                                <p class="form-hint">Common: 1 (per-second), 6 (6/6), 60 (per-minute)</p>
                                <x-input-error :messages="$errors->get('billing_increment')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Validity & Status --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Validity & Status</h3>
                        <p class="form-card-subtitle">Manage rate activation and expiry</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label for="effective_date" class="form-label">Effective Date</label>
                                <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', $rate->effective_date?->format('Y-m-d')) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('effective_date')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $rate->end_date?->format('Y-m-d')) }}" class="form-input">
                                <p class="form-hint">Leave empty for no expiry</p>
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', $rate->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="disabled" {{ old('status', $rate->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.rate-groups.rates.show', [$rateGroup, $rate]) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Rate
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Current Rate Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Current Rate Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800 form-input-mono">{{ $rate->prefix }}</p>
                                <p class="text-xs text-indigo-600">{{ $rate->destination }}</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Rate Group</span>
                                <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ $rateGroup->name }}</a>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Current Rate</span>
                                <span class="font-semibold text-gray-900 form-input-mono">${{ number_format($rate->rate_per_minute, 6) }}/min</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Status</span>
                                @if($rate->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-gray">Disabled</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $rate->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing Increments --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Billing Increments</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">1 second</span>
                            </div>
                            <p class="text-xs text-gray-500">Per-second billing. Most accurate, customer-friendly.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">6 seconds</span>
                            </div>
                            <p class="text-xs text-gray-500">6/6 billing. Industry standard for wholesale.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-success">60 seconds</span>
                            </div>
                            <p class="text-xs text-gray-500">Per-minute billing. Higher margin, simpler math.</p>
                        </div>
                    </div>
                </div>

                {{-- Cost Calculator --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Cost Examples</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-600">1 minute call</span>
                                <span class="form-input-mono text-gray-900">${{ number_format($rate->rate_per_minute + $rate->connection_fee, 6) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-600">5 minute call</span>
                                <span class="form-input-mono text-gray-900">${{ number_format(($rate->rate_per_minute * 5) + $rate->connection_fee, 6) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-600">10 minute call</span>
                                <span class="form-input-mono text-gray-900">${{ number_format(($rate->rate_per_minute * 10) + $rate->connection_fee, 6) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-600">30 minute call</span>
                                <span class="form-input-mono text-gray-900">${{ number_format(($rate->rate_per_minute * 30) + $rate->connection_fee, 6) }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-3">Based on current rate + connection fee</p>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Tips</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Changes take effect immediately for new calls</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>In-progress calls use the rate at call start</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Rate cache refreshes every 5 minutes</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
