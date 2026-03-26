<x-admin-layout>
    <x-slot name="header">Adjust Balance</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Adjust Balance</h2>
                <p class="page-subtitle">Credit or debit a user's account balance</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.transactions.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Transactions
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.balance.store') }}" x-data="{
        userOpen: false,
        userSearch: '',
        userId: '{{ old('user_id') }}',
        selectedUser: null,
        users: {{ $users->toJson() }},
        operation: '{{ old('operation', 'credit') }}',
        amount: '{{ old('amount') }}',
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
        formatBalance(balance) {
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(balance || 0);
        }
    }" x-init="
        @if(old('user_id'))
            selectedUser = users.find(u => u.id == {{ old('user_id') }});
            if (selectedUser) userSearch = selectedUser.name;
        @endif
    ">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- User Selection --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Select User</h3>
                        <p class="form-card-subtitle">Choose the account to adjust</p>
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
                                            <div class="text-right">
                                                <span class="text-xs px-2 py-0.5 rounded-full"
                                                      :class="user.role === 'reseller' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'"
                                                      x-text="user.role.charAt(0).toUpperCase() + user.role.slice(1)"></span>
                                                <p class="text-xs text-gray-500 mt-1">Balance: {{ currency_symbol() }}<span x-text="formatBalance(user.balance)"></span></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                {{-- No results --}}
                                <div x-show="userOpen && userSearch && filteredUsers.length === 0"
                                     x-cloak
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                    No users found matching "<span x-text="userSearch"></span>"
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        {{-- Selected User Info --}}
                        <div x-show="selectedUser" x-cloak class="mt-4">
                            <div class="balance-user-card">
                                <div class="balance-user-avatar" :class="selectedUser?.role === 'reseller' ? 'balance-user-avatar-reseller' : 'balance-user-avatar-client'">
                                    <span x-text="selectedUser?.name?.substring(0, 1).toUpperCase()"></span>
                                </div>
                                <div class="balance-user-info">
                                    <span class="balance-user-name" x-text="selectedUser?.name"></span>
                                    <span class="balance-user-email" x-text="selectedUser?.email"></span>
                                </div>
                                <div class="balance-user-balance">
                                    <span class="balance-user-balance-label">Current Balance</span>
                                    <span class="balance-user-balance-value">{{ currency_symbol() }}<span x-text="formatBalance(selectedUser?.balance)"></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Operation & Amount --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Adjustment Details</h3>
                        <p class="form-card-subtitle">Specify the operation type and amount</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group md:col-span-2">
                                <label class="form-label">Operation Type</label>
                                <div class="balance-operation-grid">
                                    <label class="balance-operation-option" :class="{ 'balance-operation-credit-active': operation === 'credit' }">
                                        <input type="radio" name="operation" value="credit" x-model="operation" class="sr-only">
                                        <div class="balance-operation-icon balance-operation-icon-credit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <div class="balance-operation-text">
                                            <span class="balance-operation-title">Credit (Top Up)</span>
                                            <span class="balance-operation-desc">Add funds to account</span>
                                        </div>
                                        <div class="balance-operation-check" x-show="operation === 'credit'">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </label>
                                    <label class="balance-operation-option" :class="{ 'balance-operation-debit-active': operation === 'debit' }">
                                        <input type="radio" name="operation" value="debit" x-model="operation" class="sr-only">
                                        <div class="balance-operation-icon balance-operation-icon-debit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </div>
                                        <div class="balance-operation-text">
                                            <span class="balance-operation-title">Debit (Deduct)</span>
                                            <span class="balance-operation-desc">Remove funds from account</span>
                                        </div>
                                        <div class="balance-operation-check" x-show="operation === 'debit'">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('operation')" class="mt-2" />
                            </div>

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
                                <div class="balance-preview" :class="operation === 'credit' ? 'balance-preview-credit' : 'balance-preview-debit'">
                                    <span class="balance-preview-label">After adjustment:</span>
                                    <span class="balance-preview-value">
                                        {{ currency_symbol() }}<span x-text="formatBalance(
                                            operation === 'credit'
                                                ? (parseFloat(selectedUser?.balance || 0) + parseFloat(amount || 0))
                                                : (parseFloat(selectedUser?.balance || 0) - parseFloat(amount || 0))
                                        )"></span>
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
                                    <option value="stripe" {{ old('source') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                                    <option value="paypal" {{ old('source') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                                    <option value="promotional" {{ old('source') === 'promotional' ? 'selected' : '' }}>Promotional</option>
                                    <option value="refund" {{ old('source') === 'refund' ? 'selected' : '' }}>Refund</option>
                                    <option value="correction" {{ old('source') === 'correction' ? 'selected' : '' }}>Correction</option>
                                    <option value="other" {{ old('source') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <p class="form-hint">Payment method or reason category</p>
                                <x-input-error :messages="$errors->get('source')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="remarks" class="form-label">Remarks</label>
                                <input type="text" id="remarks" name="remarks" value="{{ old('remarks') }}" class="form-input" placeholder="e.g., TXN-12345, Receipt #789">
                                <p class="form-hint">Reference number or short note</p>
                                <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="notes" class="form-label">Notes / Reason</label>
                                <textarea id="notes" name="notes" rows="3" class="form-input" placeholder="Enter reason for this adjustment...">{{ old('notes') }}</textarea>
                                <p class="form-hint">This will be recorded in the transaction log for audit purposes</p>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>

                            {{-- Also adjust parent reseller --}}
                            <div class="form-group md:col-span-2"
                                 x-show="selectedUser && selectedUser.parent && selectedUser.parent.role === 'reseller'"
                                 x-cloak>
                                <div class="flex items-start gap-3 p-3 rounded-lg border border-amber-200 bg-amber-50">
                                    <input type="checkbox" name="adjust_reseller" value="1" id="adjustReseller"
                                           class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <label for="adjustReseller" class="text-sm">
                                        <span class="font-medium text-gray-900"
                                              x-text="operation === 'credit' ? 'Also credit parent reseller' : 'Also debit parent reseller'"></span>
                                        <span class="block text-gray-500 mt-0.5"
                                              x-text="selectedUser?.parent?.name + ' — same amount will be applied'"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.transactions.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Apply Adjustment
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
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Audit Logged</p>
                                <p class="text-xs text-amber-600">All adjustments are recorded</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Transaction created instantly</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Balance updated atomically</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Creator info recorded</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Operation Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Operation Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-success">Credit</span>
                            </div>
                            <p class="text-xs text-gray-500">Add funds to user's balance. Use for manual top-ups, refunds, or promotional credits.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-danger">Debit</span>
                            </div>
                            <p class="text-xs text-gray-500">Remove funds from balance. Use for corrections, chargebacks, or manual deductions.</p>
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
                                <span class="text-gray-700">bKash / Nagad / Rocket</span>
                                <span class="text-xs text-gray-500">MFS</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-700">Stripe / PayPal</span>
                                <span class="text-xs text-gray-500">Online</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-700">Promotional</span>
                                <span class="text-xs text-gray-500">Bonus</span>
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
                                <span>Cannot debit below zero balance</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Always add a descriptive note</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use Stripe for customer payments</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Review audit logs regularly</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
