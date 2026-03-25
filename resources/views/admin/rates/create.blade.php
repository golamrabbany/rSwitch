<x-admin-layout>
    <x-slot name="header">Add Rate</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Add Rate</h2>
                <p class="page-subtitle">Add a new rate to: {{ $rateGroup->name }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Rate Group
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.rate-groups.rates.store', $rateGroup) }}">
        @csrf

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
                                <input type="text" id="prefix" name="prefix" value="{{ old('prefix') }}" required placeholder="e.g. 1201" class="form-input form-input-mono">
                                <p class="form-hint">Numeric prefix for destination matching (1-20 digits)</p>
                                <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="destination" class="form-label">Destination Name</label>
                                <input type="text" id="destination" name="destination" value="{{ old('destination') }}" required placeholder="e.g. United States - New Jersey" class="form-input">
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
                                <input type="number" id="rate_per_minute" name="rate_per_minute" value="{{ old('rate_per_minute') }}" required step="0.000001" min="0" placeholder="0.015000" class="form-input form-input-mono">
                                <p class="form-hint">Up to 6 decimal places</p>
                                <x-input-error :messages="$errors->get('rate_per_minute')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="connection_fee" class="form-label">Connection Fee ($)</label>
                                <input type="number" id="connection_fee" name="connection_fee" value="{{ old('connection_fee', '0') }}" step="0.000001" min="0" placeholder="0.000000" class="form-input form-input-mono">
                                <p class="form-hint">One-time fee per call (optional)</p>
                                <x-input-error :messages="$errors->get('connection_fee')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="min_duration" class="form-label">Minimum Duration (seconds)</label>
                                <input type="number" id="min_duration" name="min_duration" value="{{ old('min_duration', '0') }}" min="0" class="form-input">
                                <p class="form-hint">Minimum billable seconds (0 = no minimum)</p>
                                <x-input-error :messages="$errors->get('min_duration')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="billing_increment" class="form-label">Billing Increment (seconds)</label>
                                <input type="number" id="billing_increment" name="billing_increment" value="{{ old('billing_increment', '6') }}" min="1" class="form-input">
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
                        <p class="form-card-subtitle">Set when this rate becomes active</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="form-group">
                                <label for="rate_type" class="form-label">Rate Type</label>
                                <select id="rate_type" name="rate_type" class="form-input">
                                    <option value="regular" {{ old('rate_type', 'regular') === 'regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="broadcast" {{ old('rate_type') === 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                                </select>
                                <p class="form-hint">Regular for normal calls, Broadcast for voice broadcast calls</p>
                                <x-input-error :messages="$errors->get('rate_type')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="effective_date" class="form-label">Effective Date</label>
                                <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', now()->format('Y-m-d')) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('effective_date')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" class="form-input">
                                <p class="form-hint">Leave empty for no expiry</p>
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Rate
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Rate Group Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Rate Group</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800">{{ $rateGroup->name }}</p>
                                <p class="text-xs text-indigo-600">{{ ucfirst($rateGroup->type) }} Rate Group</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Current Rates</span>
                                <span class="font-semibold text-gray-900">{{ number_format($rateGroup->rates_count ?? $rateGroup->rates()->count()) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Assigned Users</span>
                                <span class="font-semibold text-gray-900">{{ number_format($rateGroup->users_count ?? $rateGroup->users()->count()) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-500">Type</span>
                                @if($rateGroup->type === 'admin')
                                    <span class="badge badge-blue">Admin</span>
                                @else
                                    <span class="badge badge-purple">Reseller</span>
                                @endif
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

                {{-- Common Prefixes --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Common Prefixes</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="form-input-mono text-gray-700">1</span>
                                <span class="text-xs text-gray-500">USA/Canada</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="form-input-mono text-gray-700">44</span>
                                <span class="text-xs text-gray-500">United Kingdom</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="form-input-mono text-gray-700">49</span>
                                <span class="text-xs text-gray-500">Germany</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="form-input-mono text-gray-700">880</span>
                                <span class="text-xs text-gray-500">Bangladesh</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="form-input-mono text-gray-700">91</span>
                                <span class="text-xs text-gray-500">India</span>
                            </div>
                        </div>
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
                                <span>Longer prefixes take priority (longest match wins)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use 6 decimal places for precision pricing</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Set effective date in future for scheduled rate changes</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use CSV import for bulk rate updates</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
