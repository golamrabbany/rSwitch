<x-admin-layout>
    <x-slot name="header">Rate Group Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $rateGroup->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @if($rateGroup->type === 'admin')
                        <span class="badge badge-blue">Admin</span>
                    @else
                        <span class="badge badge-purple">Reseller</span>
                    @endif
                    <span class="text-sm text-gray-500">{{ $rateGroup->description ?: 'No description' }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.rate-groups.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
            <a href="{{ route('admin.rate-groups.edit', $rateGroup) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <form method="POST" action="{{ route('admin.rate-groups.destroy', $rateGroup) }}" class="inline" onsubmit="return confirm('Delete this rate group and all its rates?')">
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

    <div x-data="{
        importModal: false,
        rateModal: false,
        rateMode: 'add',
        rate: { id: '', prefix: '', destination: '', rate_per_minute: '', connection_fee: '0', min_duration: '0', billing_increment: '1', effective_date: '{{ now()->format('Y-m-d') }}', end_date: '', status: 'active', rate_type: 'regular' },
        openAdd() { this.rateMode = 'add'; this.rate = { id: '', prefix: '', destination: '', rate_per_minute: '', connection_fee: '0', min_duration: '0', billing_increment: '1', effective_date: '{{ now()->format('Y-m-d') }}', end_date: '', status: 'active', rate_type: 'regular' }; this.rateModal = true; },
        openEdit(r) { this.rateMode = 'edit'; this.rate = { ...r }; this.rateModal = true; },
        openView(r) { this.rateMode = 'view'; this.rate = { ...r }; this.rateModal = true; },
    }">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="stat-icon bg-indigo-100">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ number_format($rateGroup->rates_count) }}</p>
                    <p class="stat-label">Total Rates</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ number_format($rateGroup->users_count) }}</p>
                    <p class="stat-label">Assigned Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-sky-100">
                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ ucfirst($rateGroup->type) }}</p>
                    <p class="stat-label">Type</p>
                </div>
            </div>
            @if($rateGroup->parentRateGroup)
                <div class="stat-card">
                    <div class="stat-icon bg-purple-100">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <a href="{{ route('admin.rate-groups.show', $rateGroup->parentRateGroup) }}" class="stat-value text-indigo-600 hover:text-indigo-800 text-base">{{ $rateGroup->parentRateGroup->name }}</a>
                        <p class="stat-label">Parent Group</p>
                    </div>
                </div>
            @else
                <div class="stat-card">
                    <div class="stat-icon bg-gray-100">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-value text-base">{{ $rateGroup->created_at->format('M d, Y') }}</p>
                        <p class="stat-label">Created</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Rates Section --}}
        <div class="detail-card">
            <div class="detail-card-header flex items-center justify-between">
                <h3 class="detail-card-title">Rates ({{ number_format($rateGroup->rates_count) }})</h3>
                <div class="flex items-center gap-2">
                    <button @click="importModal = true" type="button" class="btn-action-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import
                    </button>
                    <a href="{{ route('admin.rate-groups.export', $rateGroup) }}" class="btn-action-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export
                    </a>
                    <button @click="openAdd()" type="button" class="btn-action-primary-admin">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Rate
                    </button>
                </div>
            </div>

            {{-- Rates Filter --}}
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                <form method="GET" class="flex items-center gap-3 flex-wrap">
                    <div class="filter-search-box flex-1 max-w-xs">
                        <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="prefix" value="{{ request('prefix') }}" placeholder="Search prefix..." class="filter-input">
                    </div>
                    <input type="text" name="destination" value="{{ request('destination') }}" placeholder="Search destination..." class="filter-input max-w-xs">
                    <select name="status" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <a href="{{ route('admin.rate-groups.show', array_merge(['rate_group' => $rateGroup->id], request()->except('rate_type', 'page'))) }}" class="px-3 py-2 text-sm font-medium {{ !request('rate_type') ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">All</a>
                        <a href="{{ route('admin.rate-groups.show', array_merge(['rate_group' => $rateGroup->id], request()->except('page'), ['rate_type' => 'regular'])) }}" class="px-3 py-2 text-sm font-medium border-l {{ request('rate_type') === 'regular' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">Regular</a>
                        <a href="{{ route('admin.rate-groups.show', array_merge(['rate_group' => $rateGroup->id], request()->except('page'), ['rate_type' => 'broadcast'])) }}" class="px-3 py-2 text-sm font-medium border-l {{ request('rate_type') === 'broadcast' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">Broadcast</a>
                    </div>
                    <button type="submit" class="btn-search-admin">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Filter
                    </button>
                    @if(request()->hasAny(['prefix', 'destination', 'status', 'rate_type']))
                        <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="btn-clear">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Summary Bar --}}
            @if($rates->total() > 0)
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Total : {{ number_format($rates->total()) }} &middot; Showing {{ $rates->firstItem() }}–{{ $rates->lastItem() }}
                    </span>
                </div>
            @endif

            {{-- Rates Table --}}
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prefix</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Destination</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate/Min</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Min Dur</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Increment</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Effective</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rates as $rate)
                        <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                            <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $rates->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2">
                                <span class="font-mono font-bold text-gray-900">{{ $rate->prefix }}</span>
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ $rate->destination }}</td>
                            <td class="px-3 py-2 text-right font-bold text-gray-900 tabular-nums">{{ format_currency($rate->rate_per_minute, 4) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600 tabular-nums">{{ $rate->min_duration }}s</td>
                            <td class="px-3 py-2 text-right text-gray-600 tabular-nums">{{ $rate->billing_increment }}s</td>
                            <td class="px-3 py-2 text-gray-600">{{ $rate->effective_date?->format('M d, Y') }}</td>
                            <td class="px-3 py-2">
                                @if($rate->status === 'active')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Disabled</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                    <button @click="openView({id:'{{$rate->id}}', prefix:'{{$rate->prefix}}', destination:'{{$rate->destination}}', rate_per_minute:'{{$rate->rate_per_minute}}', connection_fee:'{{$rate->connection_fee}}', min_duration:'{{$rate->min_duration}}', billing_increment:'{{$rate->billing_increment}}', effective_date:'{{$rate->effective_date?->format('Y-m-d')}}', end_date:'{{$rate->end_date?->format('Y-m-d')}}', status:'{{$rate->status}}', rate_type:'{{$rate->rate_type ?? 'regular'}}'})" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button @click="openEdit({id:'{{$rate->id}}', prefix:'{{$rate->prefix}}', destination:'{{$rate->destination}}', rate_per_minute:'{{$rate->rate_per_minute}}', connection_fee:'{{$rate->connection_fee}}', min_duration:'{{$rate->min_duration}}', billing_increment:'{{$rate->billing_increment}}', effective_date:'{{$rate->effective_date?->format('Y-m-d')}}', end_date:'{{$rate->end_date?->format('Y-m-d')}}', status:'{{$rate->status}}', rate_type:'{{$rate->rate_type ?? 'regular'}}'})" class="p-1.5 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('admin.rate-groups.rates.destroy', [$rateGroup, $rate]) }}" class="inline" onsubmit="return confirm('Delete this rate?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-700 hover:bg-red-50 transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-gray-400">No rates in this group</p>
                                <button @click="openAdd()" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Add a rate</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($rates->hasPages())
                <div class="mt-4 flex justify-end px-4 py-3">
                    {{ $rates->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
                </div>
            @endif
        </div>

        {{-- Recent Imports --}}
        @if($recentImports->isNotEmpty())
        <div class="detail-card mt-6">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Recent Imports</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Uploaded By</th>
                            <th class="text-right">Imported</th>
                            <th class="text-right">Skipped</th>
                            <th class="text-right">Errors</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentImports as $imp)
                            <tr>
                                <td class="font-medium">{{ $imp->file_name }}</td>
                                <td>{{ $imp->uploader?->name ?? '-' }}</td>
                                <td class="text-right font-mono text-emerald-600">{{ number_format($imp->imported_rows) }}</td>
                                <td class="text-right font-mono text-amber-600">{{ number_format($imp->skipped_rows) }}</td>
                                <td class="text-right font-mono text-red-600">{{ number_format($imp->error_rows) }}</td>
                                <td>
                                    @if($imp->status === 'completed')
                                        <span class="badge badge-success">Completed</span>
                                    @elseif($imp->status === 'failed')
                                        <span class="badge badge-danger">Failed</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($imp->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-gray-500">{{ $imp->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Import Modal --}}
        <div x-show="importModal"
             x-cloak
             class="relative z-50"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="importModal = false">
            {{-- Backdrop --}}
            <div x-show="importModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            {{-- Modal Container --}}
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="importModal = false">
                    {{-- Modal Panel --}}
                    <div x-show="importModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-2xl"
                         @click.stop>
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Import Rates from XLSX</h3>
                                    <p class="text-sm text-gray-500">Upload an XLSX file to import rates into {{ $rateGroup->name }}</p>
                                </div>
                            </div>
                            <button @click="importModal = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('admin.rate-groups.import', $rateGroup) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="px-6 py-5 space-y-5">
                                {{-- File Upload --}}
                                <div class="form-group">
                                    <label class="form-label">CSV File</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors bg-gray-50">
                                        <div class="space-y-1 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="rate-file" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                                    <span>Upload a file</span>
                                                    <input id="rate-file" name="file" type="file" accept=".xlsx,.xls,.csv" required class="sr-only">
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">XLSX, XLS or CSV up to 10MB</p>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Import Mode --}}
                                    <div class="form-group">
                                        <label class="form-label">Import Mode</label>
                                        <select name="mode" required class="form-input">
                                            <option value="merge">Merge (update existing, add new)</option>
                                            <option value="add_only">Add Only (skip existing)</option>
                                            <option value="replace">Replace (delete all, import fresh)</option>
                                        </select>
                                    </div>

                                    {{-- Effective Date --}}
                                    <div class="form-group">
                                        <label class="form-label">Effective Date</label>
                                        <input type="date" name="effective_date" value="{{ now()->format('Y-m-d') }}" required class="form-input">
                                    </div>
                                </div>

                                {{-- File Format Help --}}
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">File Format</h4>
                                    <div class="text-xs text-gray-600 space-y-1">
                                        <p><strong>Required:</strong> prefix, destination, rate_per_minute</p>
                                        <p><strong>Optional:</strong> connection_fee, min_duration, billing_increment, end_date, status, rate_type</p>
                                    </div>
                                    <div class="mt-3 p-2 bg-white rounded border border-gray-200 font-mono text-xs text-gray-700 overflow-x-auto">
                                        <pre>prefix,destination,rate_per_minute,connection_fee
1,USA,0.015,0
44,UK,0.025,0
880,Bangladesh,0.035,0</pre>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <button type="button" @click="importModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" class="btn-primary">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    Import Rates
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- Rate Add/Edit/View Modal --}}
        <div x-show="rateModal" x-cloak class="relative z-50" role="dialog" aria-modal="true" @keydown.escape.window="rateModal = false">
            {{-- Backdrop --}}
            <div x-show="rateModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            {{-- Modal Container --}}
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="rateModal = false">
                    {{-- Modal Panel --}}
                    <div x-show="rateModal"
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-2xl"
                         @click.stop>

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                     :class="rateMode === 'view' ? 'bg-blue-100' : (rateMode === 'edit' ? 'bg-amber-100' : 'bg-emerald-100')">
                                    <template x-if="rateMode === 'add'">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </template>
                                    <template x-if="rateMode === 'edit'">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </template>
                                    <template x-if="rateMode === 'view'">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900" x-text="rateMode === 'add' ? 'Add Rate' : (rateMode === 'edit' ? 'Edit Rate' : 'Rate Details')"></h3>
                                    <p class="text-sm text-gray-500">{{ $rateGroup->name }}</p>
                                </div>
                            </div>
                            <button @click="rateModal = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="px-6 py-5">
                            {{-- View Mode --}}
                            <template x-if="rateMode === 'view'">
                                <div>
                                    {{-- Hero: Prefix + Destination + Rate --}}
                                    <div class="flex items-center gap-4 p-4 rounded-xl bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-100 mb-5">
                                        <div class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                                            <span class="font-mono text-xl font-bold text-indigo-600" x-text="rate.prefix"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-lg font-semibold text-gray-900" x-text="rate.destination"></p>
                                            <div class="flex items-center gap-3 mt-1">
                                                <span x-show="rate.status === 'active'" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>
                                                <span x-show="rate.status !== 'active'" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Disabled</span>
                                                <span class="text-xs text-gray-500 capitalize" x-text="rate.rate_type"></span>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-xs text-gray-500">Rate/Min</p>
                                            <p class="text-2xl font-bold text-indigo-600" x-text="'{{ currency_symbol() }}' + rate.rate_per_minute"></p>
                                        </div>
                                    </div>

                                    {{-- Pricing Grid --}}
                                    <div class="mb-6 mt-6" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.75rem;">
                                        <div class="p-3 rounded-lg border border-gray-200">
                                            <p class="text-xs text-gray-500 mb-0.5">Connection Fee</p>
                                            <p class="text-sm font-semibold text-gray-900" x-text="'{{ currency_symbol() }}' + rate.connection_fee"></p>
                                        </div>
                                        <div class="p-3 rounded-lg border border-gray-200">
                                            <p class="text-xs text-gray-500 mb-0.5">Min Duration</p>
                                            <p class="text-sm font-semibold text-gray-900" x-text="rate.min_duration + 's'"></p>
                                        </div>
                                        <div class="p-3 rounded-lg border border-gray-200">
                                            <p class="text-xs text-gray-500 mb-0.5">Increment</p>
                                            <p class="text-sm font-semibold text-gray-900" x-text="rate.billing_increment + 's'"></p>
                                        </div>
                                    </div>

                                    {{-- Cost Examples --}}
                                    <div class="mb-6">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Cost Examples</p>
                                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:0.5rem;">
                                            <template x-for="min in [1, 3, 5, 10]" :key="min">
                                                <div class="text-center p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                                                    <p class="text-xs text-gray-500" x-text="min + ' min'"></p>
                                                    <p class="text-sm font-bold text-gray-900 mt-0.5" x-text="'{{ currency_symbol() }}' + (parseFloat(rate.connection_fee || 0) + parseFloat(rate.rate_per_minute) * min).toFixed(4)"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Validity --}}
                                    <div class="flex items-center gap-4 p-4 rounded-lg bg-gray-50 border border-gray-100 mb-6">
                                        <div class="flex items-center gap-2 flex-1">
                                            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <div>
                                                <p class="text-xs text-gray-500">Effective</p>
                                                <p class="text-sm font-medium text-gray-900" x-text="rate.effective_date"></p>
                                            </div>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        <div class="flex items-center gap-2 flex-1">
                                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <div>
                                                <p class="text-xs text-gray-500">End Date</p>
                                                <p class="text-sm font-medium text-gray-900" x-text="rate.end_date || 'No expiry'"></p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                                        <button type="button" @click="rateModal = false" class="btn-secondary">Close</button>
                                        <button type="button" @click="openEdit(rate)" class="btn-action-primary-admin">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit Rate
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Add/Edit Form --}}
                            <template x-if="rateMode !== 'view'">
                                <form :action="rateMode === 'add' ? '{{ route('admin.rate-groups.rates.store', $rateGroup) }}' : '/admin/rate-groups/{{ $rateGroup->id }}/rates/' + rate.id" method="POST">
                                    @csrf
                                    <template x-if="rateMode === 'edit'">
                                        <input type="hidden" name="_method" value="PUT">
                                    </template>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">Prefix</label>
                                            <input type="text" name="prefix" x-model="rate.prefix" required class="form-input font-mono" placeholder="880">
                                            <p class="form-hint">Dial prefix (digits only)</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Destination</label>
                                            <input type="text" name="destination" x-model="rate.destination" required class="form-input" placeholder="Bangladesh">
                                            <p class="form-hint">Destination name</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Rate/Min</label>
                                            <input type="number" name="rate_per_minute" x-model="rate.rate_per_minute" step="0.000001" min="0" required class="form-input font-mono" placeholder="0.05">
                                            <p class="form-hint">Cost per minute</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Connection Fee</label>
                                            <input type="number" name="connection_fee" x-model="rate.connection_fee" step="0.000001" min="0" class="form-input font-mono" placeholder="0">
                                            <p class="form-hint">One-time fee per call</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Min Duration (s)</label>
                                            <input type="number" name="min_duration" x-model="rate.min_duration" min="0" class="form-input" placeholder="0">
                                            <p class="form-hint">Minimum billable seconds</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Billing Increment (s)</label>
                                            <input type="number" name="billing_increment" x-model="rate.billing_increment" min="1" class="form-input" placeholder="6">
                                            <p class="form-hint">Round up to this interval</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Effective Date</label>
                                            <input type="date" name="effective_date" x-model="rate.effective_date" required class="form-input">
                                            <p class="form-hint">When this rate starts applying</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="end_date" x-model="rate.end_date" class="form-input">
                                            <p class="form-hint">Leave blank for no expiry</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Status</label>
                                            <select name="status" x-model="rate.status" class="form-input">
                                                <option value="active">Active</option>
                                                <option value="disabled">Disabled</option>
                                            </select>
                                            <p class="form-hint">Disabled rates are excluded from billing</p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Rate Type</label>
                                            <select name="rate_type" x-model="rate.rate_type" class="form-input">
                                                <option value="regular">Regular</option>
                                                <option value="broadcast">Broadcast</option>
                                            </select>
                                            <p class="form-hint">Regular for calls, Broadcast for campaigns</p>
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                                        <button type="button" @click="rateModal = false" class="btn-secondary">Cancel</button>
                                        <button type="submit" class="btn-action-primary-admin">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span x-text="rateMode === 'add' ? 'Add Rate' : 'Update Rate'"></span>
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
