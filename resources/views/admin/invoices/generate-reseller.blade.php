<x-admin-layout>
    <x-slot name="header">Generate Reseller Invoice</x-slot>

    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Generate Reseller Invoice</h2>
                <p class="page-subtitle">Auto-generate invoice from reseller's call usage</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.invoices.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="invoiceGenerator()">
        {{-- Form (2/3) --}}
        <div class="lg:col-span-2">
            <div class="form-card">
                <div class="form-card-header">
                    <h3 class="form-card-title">Invoice Details</h3>
                    <p class="form-card-subtitle">Select reseller and billing period</p>
                </div>
                <div class="form-card-body">
                    @if(session('warning'))
                        <div class="mb-4 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-700">{{ session('warning') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.invoices.store-reseller') }}">
                        @csrf
                        <div class="space-y-5">
                            {{-- Reseller --}}
                            <div class="form-group" x-data="resellerSearch()" @click.away="open = false">
                                <label class="form-label">Reseller</label>
                                <input type="hidden" name="reseller_id" :value="selectedId" x-model="selectedId">
                                <div class="relative">
                                    <input type="text" x-model="query" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="Search reseller..." class="form-input" style="padding-left: 1rem;" autocomplete="off">
                                    <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                                        <template x-for="r in filtered" :key="r.id">
                                            <button type="button" @click="selectedId = String(r.id); query = r.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center justify-between">
                                                <span class="font-medium text-gray-900" x-text="r.name"></span>
                                                <span class="text-xs text-gray-400" x-text="r.email"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <p class="form-hint">Select the reseller to generate invoice for</p>
                                @error('reseller_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Period --}}
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Period Start</label>
                                    <input type="date" name="period_start" x-model="periodStart" value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}" required class="form-input">
                                    <p class="form-hint">First day of billing period</p>
                                    @error('period_start') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Period End</label>
                                    <input type="date" name="period_end" x-model="periodEnd" value="{{ old('period_end', now()->endOfMonth()->format('Y-m-d')) }}" required class="form-input">
                                    <p class="form-hint">Last day of billing period</p>
                                    @error('period_end') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            {{-- Preview --}}
                            <div>
                                <button type="button" @click="preview()" :disabled="!selectedId || loading" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span x-text="loading ? 'Loading...' : 'Preview Charges'"></span>
                                </button>
                            </div>

                            {{-- Preview Results --}}
                            <div x-show="previewData" x-cloak>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Charge Preview</h4>
                                    <template x-if="previewData && previewData.client_charges && previewData.client_charges.length > 0">
                                        <div class="space-y-2 mb-3">
                                            <template x-for="item in previewData.client_charges" :key="item.client_name">
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-600" x-text="item.client_name"></span>
                                                    <div class="flex items-center gap-4">
                                                        <span class="text-xs text-gray-400" x-text="item.total_calls + ' calls'"></span>
                                                        <span class="font-mono font-medium text-gray-900" x-text="'{{ currency_symbol() }}' + parseFloat(item.total_cost).toFixed(4)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <div class="border-t border-gray-200 pt-3 space-y-1">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Call Charges</span>
                                            <span class="font-mono text-gray-900" x-text="'{{ currency_symbol() }}' + parseFloat(previewData?.total_call_charges || 0).toFixed(4)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">DID Charges</span>
                                            <span class="font-mono text-gray-900" x-text="'{{ currency_symbol() }}' + parseFloat(previewData?.total_did_charges || 0).toFixed(4)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm font-semibold border-t border-gray-200 pt-2 mt-2">
                                            <span class="text-gray-700">Total</span>
                                            <span class="font-mono text-gray-900" x-text="'{{ currency_symbol() }}' + parseFloat(previewData?.total_amount || 0).toFixed(4)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <a href="{{ route('admin.invoices.index') }}" class="btn-secondary">Cancel</a>
                                <button type="submit" :disabled="!selectedId" class="btn-primary disabled:opacity-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Generate Invoice
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">How it works</p>
                        <ul class="text-sm text-blue-600 mt-1 space-y-1">
                            <li>1. Select a reseller</li>
                            <li>2. Choose billing period</li>
                            <li>3. Preview charges</li>
                            <li>4. Generate invoice</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Invoice includes</h3>
                </div>
                <div class="detail-card-body text-sm text-gray-600 space-y-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Call charges per client</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>DID monthly charges</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Line item breakdown</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Uses reseller cost rates</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    var _resellers = @json($resellers->map(function ($r) { return ['id' => $r->id, 'name' => $r->name, 'email' => $r->email]; }));

    function resellerSearch() {
        return {
            open: false, query: '', selectedId: '{{ old('reseller_id') }}', filtered: _resellers.slice(0, 5),
            init() {
                if (this.selectedId) { var f = _resellers.find(function(r) { return String(r.id) === String(this.selectedId); }.bind(this)); if (f) this.query = f.name; }
                this.$watch('query', function(val) { if (!val) { this.filtered = _resellers.slice(0, 5); return; } var q = val.toLowerCase(); this.filtered = _resellers.filter(function(r) { return r.name.toLowerCase().indexOf(q) > -1 || r.email.toLowerCase().indexOf(q) > -1; }).slice(0, 5); }.bind(this));
            }
        }
    }

    function invoiceGenerator() {
        return {
            selectedId: '{{ old('reseller_id') }}',
            periodStart: '{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}',
            periodEnd: '{{ old('period_end', now()->endOfMonth()->format('Y-m-d')) }}',
            previewData: null,
            loading: false,
            preview() {
                if (!this.selectedId) return;
                this.loading = true;
                this.previewData = null;
                fetch('{{ route('admin.invoices.preview-reseller') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ reseller_id: this.selectedId, period_start: this.periodStart, period_end: this.periodEnd })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) { this.previewData = data; this.loading = false; }.bind(this))
                .catch(function() { this.loading = false; }.bind(this));
            }
        }
    }
    </script>
    @endpush
</x-admin-layout>
