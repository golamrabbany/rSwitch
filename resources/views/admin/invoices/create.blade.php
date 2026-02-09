<x-admin-layout>
    <x-slot name="header">Create Invoice</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Create Invoice</h2>
                <p class="page-subtitle">Generate a new manual invoice</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.invoices.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Invoices
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.invoices.store') }}" x-data="{
        userOpen: false,
        userSearch: '',
        userId: '{{ old('user_id') }}',
        selectedUser: null,
        users: {{ $users->toJson() }},
        callCharges: {{ old('call_charges', 0) }},
        didCharges: {{ old('did_charges', 0) }},
        taxAmount: {{ old('tax_amount', 0) }},
        get total() {
            return (parseFloat(this.callCharges) || 0) + (parseFloat(this.didCharges) || 0) + (parseFloat(this.taxAmount) || 0);
        },
        get filteredUsers() {
            if (!this.userSearch) return this.users;
            const search = this.userSearch.toLowerCase();
            return this.users.filter(u =>
                u.name.toLowerCase().includes(search) ||
                u.email.toLowerCase().includes(search)
            );
        },
        selectUser(user) {
            this.userSearch = user.name;
            this.userId = user.id;
            this.selectedUser = user;
            this.userOpen = false;
        },
        clearUser() {
            this.userSearch = '';
            this.userId = '';
            this.selectedUser = null;
            this.$refs.userInput.focus();
        },
        formatCurrency(val) {
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
        }
    }" x-init="
        @if(old('user_id'))
            selectedUser = users.find(u => u.id == {{ old('user_id') }});
            if (selectedUser) userSearch = selectedUser.name;
        @endif
    ">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- User Selection --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Customer</h3>
                        <p class="form-card-subtitle">Select the customer for this invoice</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label class="form-label">User Account</label>
                            <div class="relative">
                                <input type="hidden" name="user_id" :value="userId">
                                <div class="relative">
                                    <input type="text"
                                           x-ref="userInput"
                                           x-model="userSearch"
                                           @focus="userOpen = true"
                                           @click="userOpen = true"
                                           @input="userOpen = true"
                                           @keydown.escape="userOpen = false"
                                           @keydown.tab="userOpen = false"
                                           class="form-input pr-16"
                                           placeholder="Search user by name or email..."
                                           autocomplete="off">
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        <button type="button" x-show="userSearch" @click="clearUser()" class="p-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                {{-- Dropdown --}}
                                <div x-show="userOpen && filteredUsers.length > 0"
                                     x-cloak
                                     @click.outside="userOpen = false"
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                    <template x-for="user in filteredUsers" :key="user.id">
                                        <div @click="selectUser(user)"
                                             class="px-4 py-3 cursor-pointer hover:bg-indigo-50 flex items-center justify-between"
                                             :class="{ 'bg-indigo-50': userId == user.id }">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                                     :class="user.role === 'reseller' ? 'bg-emerald-100' : 'bg-sky-100'">
                                                    <span class="text-sm font-medium"
                                                          :class="user.role === 'reseller' ? 'text-emerald-600' : 'text-sky-600'"
                                                          x-text="user.name.substring(0, 1).toUpperCase()"></span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900" x-text="user.name"></p>
                                                    <p class="text-xs text-gray-500" x-text="user.email"></p>
                                                </div>
                                            </div>
                                            <span class="text-xs px-2 py-0.5 rounded-full"
                                                  :class="user.role === 'reseller' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'"
                                                  x-text="user.role.charAt(0).toUpperCase() + user.role.slice(1)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Billing Period --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing Period</h3>
                        <p class="form-card-subtitle">Specify the date range for this invoice</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="period_start" class="form-label">Period Start</label>
                                <input type="date" id="period_start" name="period_start" value="{{ old('period_start') }}" required class="form-input">
                                <x-input-error :messages="$errors->get('period_start')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="period_end" class="form-label">Period End</label>
                                <input type="date" id="period_end" name="period_end" value="{{ old('period_end') }}" required class="form-input">
                                <x-input-error :messages="$errors->get('period_end')" class="mt-2" />
                            </div>
                            <div class="form-group md:col-span-2">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" required class="form-input">
                                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charges --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Charges</h3>
                        <p class="form-card-subtitle">Enter the charge amounts</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label for="call_charges" class="form-label">Call Charges</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">{{ currency_symbol() }}</span>
                                    <input type="number" id="call_charges" name="call_charges" x-model="callCharges" step="0.01" min="0" required class="form-input pl-8 font-mono" placeholder="0.00">
                                </div>
                                <x-input-error :messages="$errors->get('call_charges')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="did_charges" class="form-label">DID Charges</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">{{ currency_symbol() }}</span>
                                    <input type="number" id="did_charges" name="did_charges" x-model="didCharges" step="0.01" min="0" required class="form-input pl-8 font-mono" placeholder="0.00">
                                </div>
                                <x-input-error :messages="$errors->get('did_charges')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="tax_amount" class="form-label">Tax</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">{{ currency_symbol() }}</span>
                                    <input type="number" id="tax_amount" name="tax_amount" x-model="taxAmount" step="0.01" min="0" required class="form-input pl-8 font-mono" placeholder="0.00">
                                </div>
                                <x-input-error :messages="$errors->get('tax_amount')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.invoices.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Invoice
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Total Preview --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Total Amount</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="text-center">
                            <span class="text-3xl font-bold text-gray-900">{{ currency_symbol() }}<span x-text="formatCurrency(total)"></span></span>
                            <p class="text-xs text-gray-500 mt-1">Invoice Total</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Call Charges</span>
                                <span class="text-gray-900">{{ currency_symbol() }}<span x-text="formatCurrency(callCharges)"></span></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">DID Charges</span>
                                <span class="text-gray-900">{{ currency_symbol() }}<span x-text="formatCurrency(didCharges)"></span></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tax</span>
                                <span class="text-gray-900">{{ currency_symbol() }}<span x-text="formatCurrency(taxAmount)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800">Auto-generated Number</p>
                                <p class="text-xs text-indigo-600">Invoice # will be assigned</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Created as draft initially</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Issue to send to customer</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>PDF export available</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
