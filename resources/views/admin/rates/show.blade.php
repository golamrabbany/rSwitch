<x-admin-layout>
    <x-slot name="header">Rate Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">
                    <span class="font-mono">{{ $rate->prefix }}</span>
                    <span class="text-gray-400 mx-2">—</span>
                    {{ $rate->destination }}
                </h2>
                <div class="flex items-center gap-2 mt-1">
                    @if($rate->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-gray">Disabled</span>
                    @endif
                    @if($rate->end_date && $rate->end_date->isPast())
                        <span class="badge badge-danger">Expired</span>
                    @elseif($rate->effective_date->isFuture())
                        <span class="badge badge-warning">Scheduled</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.rate-groups.rates.edit', [$rateGroup, $rate]) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <form method="POST" action="{{ route('admin.rate-groups.rates.destroy', [$rateGroup, $rate]) }}" class="inline" onsubmit="return confirm('Delete this rate?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Destination Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Destination Information</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Prefix</span>
                            <span class="detail-value font-mono text-lg">{{ $rate->prefix }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Destination</span>
                            <span class="detail-value">{{ $rate->destination }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Pricing</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Rate per Minute</span>
                            <span class="detail-value text-lg font-semibold text-indigo-600">${{ number_format($rate->rate_per_minute, 6) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Connection Fee</span>
                            <span class="detail-value">${{ number_format($rate->connection_fee ?? 0, 6) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Minimum Duration</span>
                            <span class="detail-value">{{ $rate->min_duration ?? 0 }} seconds</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Billing Increment</span>
                            <span class="detail-value">{{ $rate->billing_increment ?? 6 }} seconds</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cost Examples --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Cost Examples</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @php
                            $connectionFee = $rate->connection_fee ?? 0;
                            $ratePerMin = $rate->rate_per_minute;
                        @endphp
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">1 Minute</p>
                            <p class="text-lg font-semibold text-gray-900">${{ number_format($connectionFee + $ratePerMin, 4) }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">3 Minutes</p>
                            <p class="text-lg font-semibold text-gray-900">${{ number_format($connectionFee + ($ratePerMin * 3), 4) }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">5 Minutes</p>
                            <p class="text-lg font-semibold text-gray-900">${{ number_format($connectionFee + ($ratePerMin * 5), 4) }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">10 Minutes</p>
                            <p class="text-lg font-semibold text-gray-900">${{ number_format($connectionFee + ($ratePerMin * 10), 4) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Rate Group Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Rate Group</h3>
                </div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar avatar-indigo">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div>
                            <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                {{ $rateGroup->name }}
                            </a>
                            <p class="text-xs text-gray-500">{{ ucfirst($rateGroup->type) }} Rate Group</p>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Type</span>
                            @if($rateGroup->type === 'admin')
                                <span class="badge badge-blue">Admin</span>
                            @else
                                <span class="badge badge-purple">Reseller</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total Rates</span>
                            <span class="text-gray-900">{{ $rateGroup->rates()->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Validity Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Validity Period</h3>
                </div>
                <div class="detail-card-body">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Effective Date</p>
                                <p class="text-sm font-medium text-gray-900">{{ $rate->effective_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full {{ $rate->end_date ? 'bg-amber-100' : 'bg-gray-100' }} flex items-center justify-center">
                                <svg class="w-4 h-4 {{ $rate->end_date ? 'text-amber-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">End Date</p>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $rate->end_date ? $rate->end_date->format('M d, Y') : 'No expiry' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Status</h3>
                </div>
                <div class="detail-card-body">
                    @if($rate->status === 'active' && (!$rate->end_date || $rate->end_date->isFuture()) && $rate->effective_date->isPast())
                        <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-emerald-800">Active</p>
                                <p class="text-xs text-emerald-600">Currently in use for billing</p>
                            </div>
                        </div>
                    @elseif($rate->effective_date->isFuture())
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Scheduled</p>
                                <p class="text-xs text-amber-600">Starts {{ $rate->effective_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @elseif($rate->end_date && $rate->end_date->isPast())
                        <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-red-800">Expired</p>
                                <p class="text-xs text-red-600">Ended {{ $rate->end_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Disabled</p>
                                <p class="text-xs text-gray-600">Not in use for billing</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        View Rate Group
                    </a>
                    <a href="{{ route('admin.rate-groups.rates.create', $rateGroup) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add New Rate
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Timestamps --}}
    <div class="mt-6 text-xs text-gray-400">
        Created {{ $rate->created_at->format('M d, Y H:i') }} &bull; Updated {{ $rate->updated_at->format('M d, Y H:i') }}
    </div>

    {{-- Back Link --}}
    <div class="mt-4">
        <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to {{ $rateGroup->name }}
        </a>
    </div>
</x-admin-layout>
