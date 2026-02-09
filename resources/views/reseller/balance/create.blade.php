<x-reseller-layout>
    <x-slot name="header">Topup Client</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Topup Client</h2>
                <p class="page-subtitle">Add funds to a client's account balance</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.transactions.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Transactions
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('reseller.balance.store') }}" x-data="{
        clientOpen: false,
        clientSearch: '',
        clientId: '{{ old('user_id') }}',
        selectedClient: null,
        clients: {{ $clients->toJson() }},
        amount: '{{ old('amount') }}',
        get filteredClients() {
            if (!this.clientSearch) return this.clients;
            const search = this.clientSearch.toLowerCase();
            return this.clients.filter(c =>
                c.name.toLowerCase().includes(search) ||
                c.email.toLowerCase().includes(search)
            );
        },
        selectClient(client) {
            this.clientSearch = client.name;
            this.clientId = client.id;
            this.selectedClient = client;
            this.clientOpen = false;
        },
        clearClient() {
            this.clientSearch = '';
            this.clientId = '';
            this.selectedClient = null;
            this.$refs.clientInput.focus();
        },
        formatBalance(balance) {
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(balance || 0);
        }
    }" x-init="
        @if(old('user_id'))
            selectedClient = clients.find(c => c.id == {{ old('user_id') }});
            if (selectedClient) clientSearch = selectedClient.name;
        @endif
    ">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Client Selection --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Select Client</h3>
                        <p class="form-card-subtitle">Choose the client account to top up</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label class="form-label">Client Account</label>
                            <div class="relative">
                                <input type="hidden" name="user_id" :value="clientId">
                                <div class="relative">
                                    <input type="text"
                                           x-ref="clientInput"
                                           x-model="clientSearch"
                                           @focus="clientOpen = true"
                                           @click="clientOpen = true"
                                           @input="clientOpen = true"
                                           @keydown.escape="clientOpen = false"
                                           @keydown.tab="clientOpen = false"
                                           class="form-input pr-16"
                                           placeholder="Search client by name or email..."
                                           autocomplete="off">
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        <button type="button" x-show="clientSearch" @click="clearClient()" class="p-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500">
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
                                <div x-show="clientOpen && filteredClients.length > 0"
                                     x-cloak
                                     @click.outside="clientOpen = false"
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                    <template x-for="client in filteredClients" :key="client.id">
                                        <div @click="selectClient(client)"
                                             class="px-4 py-3 cursor-pointer hover:bg-emerald-50 flex items-center justify-between"
                                             :class="{ 'bg-emerald-50': clientId == client.id }">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-sky-600" x-text="client.name.substring(0, 1).toUpperCase()"></span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900" x-text="client.name"></p>
                                                    <p class="text-xs text-gray-500" x-text="client.email"></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">Client</span>
                                                <p class="text-xs text-gray-500 mt-1">Balance: {{ currency_symbol() }}<span x-text="formatBalance(client.balance)"></span></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                {{-- No results --}}
                                <div x-show="clientOpen && clientSearch && filteredClients.length === 0"
                                     x-cloak
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                    No clients found matching "<span x-text="clientSearch"></span>"
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        {{-- Selected Client Info --}}
                        <div x-show="selectedClient" x-cloak class="mt-4">
                            <div class="balance-user-card">
                                <div class="balance-user-avatar balance-user-avatar-client">
                                    <span x-text="selectedClient?.name?.substring(0, 1).toUpperCase()"></span>
                                </div>
                                <div class="balance-user-info">
                                    <span class="balance-user-name" x-text="selectedClient?.name"></span>
                                    <span class="balance-user-email" x-text="selectedClient?.email"></span>
                                </div>
                                <div class="balance-user-balance">
                                    <span class="balance-user-balance-label">Current Balance</span>
                                    <span class="balance-user-balance-value">{{ currency_symbol() }}<span x-text="formatBalance(selectedClient?.balance)"></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Topup Amount --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Topup Details</h3>
                        <p class="form-card-subtitle">Specify the amount and payment source</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">{{ currency_symbol() }}</span>
                                    <input type="number" id="amount" name="amount" x-model="amount" step="0.01" min="0.01" max="999999.99" required
                                           class="form-input pl-8 font-mono text-lg"
                                           placeholder="0.00">
                                </div>
                                <p class="form-hint">Enter amount between 0.01 and 999,999.99</p>
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label class="form-label">New Balance (Preview)</label>
                                <div class="balance-preview balance-preview-credit">
                                    <span class="balance-preview-label">After topup:</span>
                                    <span class="balance-preview-value">
                                        {{ currency_symbol() }}<span x-text="formatBalance(parseFloat(selectedClient?.balance || 0) + parseFloat(amount || 0))"></span>
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="source" class="form-label">Source</label>
                                <select id="source" name="source" class="form-input">
                                    <option value="">Select source...</option>
                                    <option value="cash" {{ old('source') === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('source') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="bkash" {{ old('source') === 'bkash' ? 'selected' : '' }}>bKash</option>
                                    <option value="nagad" {{ old('source') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                                    <option value="rocket" {{ old('source') === 'rocket' ? 'selected' : '' }}>Rocket</option>
                                    <option value="other" {{ old('source') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <p class="form-hint">Payment method received</p>
                                <x-input-error :messages="$errors->get('source')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="remarks" class="form-label">Remarks</label>
                                <input type="text" id="remarks" name="remarks" value="{{ old('remarks') }}" class="form-input" placeholder="e.g., TXN-12345">
                                <p class="form-hint">Reference number or short note</p>
                                <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" rows="2" class="form-input" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.transactions.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary-reseller">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Top Up Client
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Quick Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-emerald-800">Instant Credit</p>
                                <p class="text-xs text-emerald-600">Funds available immediately</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Transaction logged for records</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Client can use balance for calls</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>View in transaction history</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Sources --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Payment Sources</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-700">Cash</span>
                                <span class="text-xs text-gray-500">In-person</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-700">Bank Transfer</span>
                                <span class="text-xs text-gray-500">Wire/NEFT</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-700">bKash</span>
                                <span class="text-xs text-gray-500">Mobile</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-700">Nagad</span>
                                <span class="text-xs text-gray-500">Mobile</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-700">Rocket</span>
                                <span class="text-xs text-gray-500">Mobile</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Your Balance --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Your Balance</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="text-center">
                            <span class="text-2xl font-bold text-gray-900">{{ format_currency(auth()->user()->balance) }}</span>
                            <p class="text-xs text-gray-500 mt-1">Available in your account</p>
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
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Record payment source for tracking</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Add remarks for reference</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Client will see topup in history</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
