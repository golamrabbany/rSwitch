<x-reseller-layout>
    <x-slot name="header">{{ $tariff->name }}</x-slot>

    <div x-data="pageData()" x-cloak>
        {{-- Page Header --}}
        <div class="page-header-row">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="page-title">{{ $tariff->name }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        @if($isBaseTariff)
                            <span class="badge badge-success">Base Tariff</span>
                        @else
                            <span class="badge badge-purple">Reseller</span>
                        @endif
                        <span class="text-sm text-gray-500">{{ $tariff->description ?: 'No description' }}</span>
                    </div>
                </div>
            </div>
            <div class="page-actions">
                <a href="{{ route('reseller.tariffs.index') }}" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
                @if(!$isBaseTariff)
                    <a href="{{ route('reseller.tariffs.edit', $tariff) }}" class="btn-action-primary-reseller">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endif
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ number_format($rates->total()) }}</p>
                    <p class="stat-label">Total Rates</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ $tariff->users()->count() }}</p>
                    <p class="stat-label">Assigned Clients</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple-100">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ $isBaseTariff ? 'Base' : 'Reseller' }}</p>
                    <p class="stat-label">Type</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-gray-100">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value text-base">{{ $tariff->created_at?->format('M d, Y') }}</p>
                    <p class="stat-label">Created</p>
                </div>
            </div>
        </div>

        {{-- Rates Section --}}
        <div class="detail-card">
            <div class="detail-card-header flex items-center justify-between">
                <h3 class="detail-card-title">Rates ({{ number_format($rates->total()) }})</h3>
                <div class="flex items-center gap-2">
                    @if(!$isBaseTariff)
                        <button @click="importModal = true" type="button" class="btn-action-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Import
                        </button>
                        <a href="{{ route('reseller.tariffs.export', $tariff) }}" class="btn-action-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export
                        </a>
                        <button @click="openAdd()" type="button" class="btn-action-primary-reseller">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Rate
                        </button>
                    @endif
                </div>
            </div>

            {{-- Rates Filter --}}
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                <form method="GET" class="flex items-center gap-3 flex-wrap">
                    <div class="filter-search-box flex-1 max-w-xs">
                        <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search prefix or destination..." class="filter-input">
                    </div>
                    <button type="submit" class="btn-search-reseller">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Filter
                    </button>
                    @if(request('search'))
                        <a href="{{ route('reseller.tariffs.show', $tariff) }}" class="btn-clear">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Rates Table --}}
            <div class="overflow-x-auto">
                <table class="data-table data-table-compact">
                    <thead>
                        <tr>
                            <th>Prefix</th>
                            <th>Destination</th>
                            <th class="text-right">Rate/Min</th>
                            <th class="text-right">Conn. Fee</th>
                            <th class="text-right">Min Dur.</th>
                            <th class="text-right">Increment</th>
                            <th>Effective</th>
                            <th>Status</th>
                            @if(!$isBaseTariff)
                                <th class="text-center">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $rate)
                            <tr>
                                <td><span class="font-mono font-semibold text-emerald-600">{{ $rate->prefix }}</span></td>
                                <td>{{ $rate->destination }}</td>
                                <td class="text-right font-mono">{{ format_currency($rate->rate_per_minute, 4) }}</td>
                                <td class="text-right font-mono text-gray-500">{{ format_currency($rate->connection_fee, 4) }}</td>
                                <td class="text-right text-gray-500">{{ $rate->min_duration }}s</td>
                                <td class="text-right text-gray-500">{{ $rate->billing_increment }}s</td>
                                <td class="whitespace-nowrap text-gray-500">{{ \Carbon\Carbon::parse($rate->effective_date)->format('M d, Y h:i A') }}</td>
                                <td>
                                    @if($rate->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-gray">Disabled</span>
                                    @endif
                                </td>
                                @if(!$isBaseTariff)
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="openEdit({{ json_encode(['id' => $rate->id, 'prefix' => $rate->prefix, 'destination' => $rate->destination, 'rate_per_minute' => number_format($rate->rate_per_minute, 6, '.', ''), 'connection_fee' => number_format($rate->connection_fee, 6, '.', ''), 'min_duration' => $rate->min_duration, 'billing_increment' => $rate->billing_increment]) }})" class="action-icon" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            @if($rate->status === 'active')
                                                <button @click="deletePrefix = '{{ $rate->prefix }} — {{ addslashes($rate->destination) }}'; deleteAction = '{{ route('reseller.tariffs.delete-rate', [$tariff, $rate]) }}'; deleteModal = true" class="action-icon text-red-500 hover:text-red-600 hover:bg-red-50" title="Disable">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isBaseTariff ? 8 : 9 }}" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="empty-text">{{ request('search') ? 'No rates found for "' . request('search') . '"' : 'No rates in this group' }}</p>
                                        @if(!$isBaseTariff && !request('search'))
                                            <button @click="openAdd()" class="empty-link-reseller">Add a rate</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rates->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $rates->withQueryString()->links() }}
                </div>
            @endif
        </div>

        {{-- Add/Edit Rate Modal --}}
        @if(!$isBaseTariff)
        <div x-show="show" x-cloak class="relative z-50" @keydown.escape.window="show = false">
            <div x-show="show"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="show = false">
                    <div x-show="show"
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-lg" @click.stop>

                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="mode === 'add' ? 'bg-emerald-100' : 'bg-amber-100'">
                                    <template x-if="mode === 'add'">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </template>
                                    <template x-if="mode === 'edit'">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900" x-text="mode === 'add' ? 'Add Rate' : 'Edit Rate'"></h3>
                                    <p class="text-sm text-gray-500">{{ $tariff->name }}</p>
                                </div>
                            </div>
                            <button @click="show = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form :action="mode === 'add' ? '{{ route('reseller.tariffs.add-rate', $tariff) }}' : '{{ url('reseller/tariffs/' . $tariff->id . '/rates') }}/' + rateId" method="POST">
                            @csrf
                            <template x-if="mode === 'edit'">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <div class="px-6 py-5 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Prefix</label>
                                        <input type="text" name="prefix" x-model="form.prefix" required placeholder="e.g. 880" class="form-input font-mono">
                                        <p class="text-xs text-gray-400 mt-1">Numeric prefix (1–20 digits)</p>
                                    </div>
                                    <div>
                                        <label class="form-label">Destination</label>
                                        <input type="text" name="destination" x-model="form.destination" required placeholder="e.g. Bangladesh" class="form-input">
                                        <p class="text-xs text-gray-400 mt-1">Destination name</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Rate/Minute ({{ currency_symbol() }})</label>
                                        <input type="number" name="rate_per_minute" x-model="form.rate_per_minute" required step="0.000001" min="0" placeholder="0.020000" class="form-input font-mono">
                                        <p class="text-xs text-gray-400 mt-1">Up to 6 decimal places</p>
                                    </div>
                                    <div>
                                        <label class="form-label">Connection Fee ({{ currency_symbol() }})</label>
                                        <input type="number" name="connection_fee" x-model="form.connection_fee" step="0.000001" min="0" class="form-input font-mono">
                                        <p class="text-xs text-gray-400 mt-1">One-time fee per call</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Min Duration (s)</label>
                                        <input type="number" name="min_duration" x-model="form.min_duration" min="0" class="form-input">
                                        <p class="text-xs text-gray-400 mt-1">0 = no minimum</p>
                                    </div>
                                    <div>
                                        <label class="form-label">Billing Increment (s)</label>
                                        <input type="number" name="billing_increment" x-model="form.billing_increment" min="1" class="form-input">
                                        <p class="text-xs text-gray-400 mt-1">Common: 1, 6, or 60</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                <button type="button" @click="show = false" class="btn-secondary">Cancel</button>
                                <button type="submit" class="btn-primary-reseller">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="mode === 'add' ? 'Add Rate' : 'Update Rate'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Import Modal --}}
        @if(!$isBaseTariff)
        <div x-show="importModal" x-cloak class="relative z-50" @keydown.escape.window="importModal = false">
            <div x-show="importModal"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="importModal = false">
                    <div x-show="importModal"
                         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-lg" @click.stop>

                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Import Rates</h3>
                                    <p class="text-sm text-gray-500">Upload XLSX file to {{ $tariff->name }}</p>
                                </div>
                            </div>
                            <button @click="importModal = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('reseller.tariffs.import', $tariff) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="px-6 py-5 space-y-4">
                                <div>
                                    <label class="form-label">XLSX File</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-emerald-400 transition-colors bg-gray-50">
                                        <div class="space-y-1 text-center">
                                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="import-file" class="relative cursor-pointer font-medium text-emerald-600 hover:text-emerald-500">
                                                    <span>Choose file</span>
                                                    <input id="import-file" name="file" type="file" accept=".xlsx,.xls" required class="sr-only">
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-400">XLSX up to 10MB</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Import Mode</label>
                                    <select name="mode" required class="form-input">
                                        <option value="merge">Merge (update existing, add new)</option>
                                        <option value="add_only">Add Only (skip existing prefixes)</option>
                                        <option value="replace">Replace All (delete existing, import fresh)</option>
                                    </select>
                                </div>

                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Required Columns</h4>
                                    <p class="text-xs text-gray-500">prefix, destination, rate_per_minute</p>
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase mt-2 mb-1">Optional</h4>
                                    <p class="text-xs text-gray-500">connection_fee, min_duration, billing_increment</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                                <button type="button" @click="importModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" class="btn-primary-reseller">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        @endif

        {{-- Delete Confirmation Modal --}}
        <div x-show="deleteModal" x-cloak class="relative z-50" @keydown.escape.window="deleteModal = false">
            <div x-show="deleteModal"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="deleteModal = false">
                    <div x-show="deleteModal"
                         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-md" @click.stop>

                        <div class="p-6 text-center">
                            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Rate</h3>
                            <p class="text-sm text-gray-500 mb-1">Are you sure you want to delete this rate?</p>
                            <p class="text-sm font-medium text-gray-700 mb-1" x-text="deletePrefix"></p>
                            <p class="text-xs text-gray-400 mt-3">This rate will be removed from billing. Existing call records will not be affected.</p>
                        </div>

                        <div class="flex items-center gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <button type="button" @click="deleteModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                            <form :action="deleteAction" method="POST" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Delete Rate</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
function pageData() {
    return {
        show: false,
        mode: 'add',
        rateId: null,
        form: { prefix: '', destination: '', rate_per_minute: '', connection_fee: '0', min_duration: '0', billing_increment: '6' },
        deleteModal: false,
        deletePrefix: '',
        deleteAction: '',
        importModal: false,
        openAdd() {
            this.mode = 'add';
            this.rateId = null;
            this.form = { prefix: '', destination: '', rate_per_minute: '', connection_fee: '0', min_duration: '0', billing_increment: '6' };
            this.show = true;
        },
        openEdit(data) {
            this.mode = 'edit';
            this.rateId = data.id;
            this.form = {
                prefix: data.prefix,
                destination: data.destination,
                rate_per_minute: data.rate_per_minute,
                connection_fee: data.connection_fee,
                min_duration: String(data.min_duration),
                billing_increment: String(data.billing_increment),
            };
            this.show = true;
        }
    }
}
</script>
@endpush
</x-reseller-layout>
