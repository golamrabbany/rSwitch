<x-admin-layout>
    <x-slot name="header">DID Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title font-mono">{{ $did->number }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @if ($did->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @elseif ($did->status === 'unassigned')
                        <span class="badge badge-warning">Unassigned</span>
                    @else
                        <span class="badge badge-danger">Disabled</span>
                    @endif
                    <span class="text-sm text-gray-500">{{ $did->provider }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dids.edit', $did) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <a href="{{ route('admin.dids.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content - Left Side --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- DID Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">DID Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Number</span>
                            <span class="detail-value font-mono">{{ $did->number }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Provider</span>
                            <span class="detail-value">{{ $did->provider }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Trunk</span>
                            <span class="detail-value">
                                <a href="{{ route('admin.trunks.show', $did->trunk) }}" class="text-indigo-600 hover:text-indigo-500">
                                    {{ $did->trunk->name }}
                                </a>
                                <span class="text-gray-500">({{ $did->trunk->direction }})</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                @if ($did->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif ($did->status === 'unassigned')
                                    <span class="badge badge-warning">Unassigned</span>
                                @else
                                    <span class="badge badge-danger">Disabled</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Destination Routing --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Destination Routing</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">Destination Type</span>
                            <span class="detail-value">
                                @if ($did->destination_type === 'sip_account')
                                    <span class="badge badge-info">SIP Account</span>
                                @elseif ($did->destination_type === 'ring_group')
                                    <span class="badge badge-purple">Ring Group</span>
                                @elseif ($did->destination_type === 'external')
                                    <span class="badge badge-warning">External Number</span>
                                @else
                                    <span class="text-gray-400">Not configured</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">Destination</span>
                            <span class="detail-value">
                                @if ($did->destination_type === 'sip_account' && $destinationSip)
                                    <a href="{{ route('admin.sip-accounts.show', $destinationSip) }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $destinationSip->username }}
                                    </a>
                                    <span class="text-gray-500">— {{ $destinationSip->user->name ?? 'Unknown' }}</span>
                                @elseif ($did->destination_type === 'ring_group' && $destinationRingGroup)
                                    <a href="{{ route('admin.ring-groups.show', $destinationRingGroup) }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $destinationRingGroup->name }}
                                    </a>
                                    <span class="text-gray-500">— {{ $destinationRingGroup->members_count ?? 0 }} members, {{ ucfirst($destinationRingGroup->strategy) }}</span>
                                @elseif ($did->destination_type === 'external' && $did->destination_number)
                                    <span class="font-mono">{{ $did->destination_number }}</span>
                                @else
                                    <span class="text-gray-400">Not configured</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Assignment --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Assignment</h3>
                </div>
                <div class="detail-card-body">
                    @if ($did->assignedUser)
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">User</span>
                                <span class="detail-value">
                                    <a href="{{ route('admin.users.show', $did->assignedUser) }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $did->assignedUser->name }}
                                    </a>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Role</span>
                                <span class="detail-value">{{ ucfirst($did->assignedUser->role) }}</span>
                            </div>
                            <div class="detail-item md:col-span-2">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">{{ $did->assignedUser->email }}</span>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <p class="text-sm text-gray-500 italic">This DID is not assigned to any user.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Billing --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Billing</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 rounded-lg text-center">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Monthly Cost</p>
                            <p class="text-xl font-semibold text-gray-700 mt-1">{{ format_currency($did->monthly_cost, 4) }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg text-center">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Monthly Price</p>
                            <p class="text-xl font-semibold text-gray-900 mt-1">{{ format_currency($did->monthly_price, 4) }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg text-center">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Margin</p>
                            @php
                                $margin = $did->monthly_price - $did->monthly_cost;
                                $marginPct = $did->monthly_price > 0 ? ($margin / $did->monthly_price) * 100 : 0;
                            @endphp
                            <p class="text-xl font-semibold mt-1 {{ $margin >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ format_currency($margin, 4) }}
                                <span class="text-sm font-normal">({{ number_format($marginPct, 1) }}%)</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar - Right Side --}}
        <div class="space-y-6">
            {{-- DID Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">DID Info</h3>
                </div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-mono font-medium text-gray-900">{{ $did->number }}</p>
                            <span class="badge {{ $did->status === 'active' ? 'badge-success' : ($did->status === 'unassigned' ? 'badge-warning' : 'badge-danger') }}">
                                {{ ucfirst($did->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                        <div class="flex justify-between">
                            <span class="text-gray-500">DID ID</span>
                            <span class="font-mono text-gray-900">#{{ $did->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Created</span>
                            <span class="text-gray-900">{{ $did->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Updated</span>
                            <span class="text-gray-900">{{ $did->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('admin.dids.edit', $did) }}" class="flex items-center gap-2 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span class="text-sm text-gray-700">Edit DID</span>
                    </a>
                    @if($did->assignedUser)
                    <a href="{{ route('admin.users.show', $did->assignedUser) }}" class="flex items-center gap-2 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-sm text-gray-700">View Owner</span>
                    </a>
                    @endif
                    <a href="{{ route('admin.trunks.show', $did->trunk) }}" class="flex items-center gap-2 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="text-sm text-gray-700">View Trunk</span>
                    </a>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="detail-card border-red-200">
                <div class="detail-card-header">
                    <h3 class="detail-card-title text-red-600">Danger Zone</h3>
                </div>
                <div class="detail-card-body">
                    <p class="text-xs text-gray-500 mb-3">Deleting this DID will remove it permanently. This action cannot be undone.</p>
                    <form method="POST" action="{{ route('admin.dids.destroy', $did) }}"
                          onsubmit="return confirm('Delete DID {{ $did->number }}? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full btn-danger text-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete DID
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
