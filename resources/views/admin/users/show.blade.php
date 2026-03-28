@php
    $isReseller = $user->role === 'reseller';
    $isClient = $user->role === 'client';
    $pageTitle = $isReseller ? 'Reseller Details' : ($isClient ? 'Client Details' : 'User Details');
    $backRoute = $isReseller
        ? route('admin.users.index', ['role' => 'reseller'])
        : ($isClient ? route('admin.users.index', ['role' => 'client']) : route('admin.users.index'));
    $themeColor = $isReseller ? 'emerald' : ($isClient ? 'sky' : 'indigo');
@endphp

<x-admin-layout>
    <x-slot name="header">{{ $pageTitle }}</x-slot>

    {{-- Page Header with User Info --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Left: User Info --}}
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-{{ $themeColor }}-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl font-bold text-{{ $themeColor }}-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $user->name }}</h2>
                        <span class="badge badge-{{ $isReseller ? 'purple' : ($isClient ? 'blue' : 'gray') }}">{{ ucfirst($user->role) }}</span>
                        @if($user->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($user->status === 'suspended')
                            <span class="badge badge-warning">Suspended</span>
                        @else
                            <span class="badge badge-danger">Disabled</span>
                        @endif
                    </div>
                    <p class="text-gray-500 mt-1">{{ $user->email }}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Joined {{ $user->created_at->format('M d, Y') }}
                        </span>
                        @if($user->parent)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Parent: @if($user->parent->isSuperAdmin())<span class="text-indigo-600 font-medium">Direct (Super Admin)</span>@else<a href="{{ route('admin.users.show', $user->parent) }}" class="text-indigo-600 hover:text-indigo-800">{{ $user->parent->name }}</a>@endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-3" x-data="{ balanceModalOpen: false }">
                @if($isReseller || $isClient)
                    <button type="button" @click="balanceModalOpen = true" class="btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Adjust Balance
                    </button>

                    {{-- Balance Adjustment Modal --}}
                    <div x-show="balanceModalOpen"
                         x-cloak
                         class="fixed inset-0 z-50 overflow-y-auto"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        {{-- Backdrop --}}
                        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="balanceModalOpen = false"></div>

                        {{-- Modal Content --}}
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl"
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="balanceModalOpen = false">

                                {{-- Modal Header --}}
                                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Adjust Balance</h3>
                                            <p class="text-sm text-gray-500">{{ $user->name }}</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="balanceModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Modal Body --}}
                                <div class="p-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        {{-- Left: Adjustment Form --}}
                                        <div>
                                            {{-- Current Balance Card --}}
                                            <div class="p-4 rounded-xl mb-6 {{ $user->balance >= 0 ? 'bg-indigo-50 border border-indigo-200' : 'bg-red-50 border border-red-200' }}">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-medium {{ $user->balance >= 0 ? 'text-indigo-700' : 'text-red-700' }}">Current Balance</span>
                                                    <span class="text-2xl font-bold {{ $user->balance >= 0 ? 'text-indigo-700' : 'text-red-700' }}">{{ format_currency($user->balance) }}</span>
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('admin.users.adjust-balance', $user) }}" x-data="{ operation: 'credit' }">
                                                @csrf
                                                {{-- Operation Toggle --}}
                                                <div class="form-group mb-4">
                                                    <label class="form-label">Operation</label>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <label class="relative cursor-pointer">
                                                            <input type="radio" name="operation" value="credit" x-model="operation" class="sr-only peer">
                                                            <div class="p-3 rounded-lg border-2 text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                                                                <svg class="w-6 h-6 mx-auto mb-1" :class="operation === 'credit' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                                </svg>
                                                                <span class="text-sm font-medium" :class="operation === 'credit' ? 'text-indigo-700' : 'text-gray-600'">Credit</span>
                                                            </div>
                                                        </label>
                                                        <label class="relative cursor-pointer">
                                                            <input type="radio" name="operation" value="debit" x-model="operation" class="sr-only peer">
                                                            <div class="p-3 rounded-lg border-2 text-center transition-all peer-checked:border-red-500 peer-checked:bg-red-50 border-gray-200 hover:border-gray-300">
                                                                <svg class="w-6 h-6 mx-auto mb-1" :class="operation === 'debit' ? 'text-red-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                                </svg>
                                                                <span class="text-sm font-medium" :class="operation === 'debit' ? 'text-red-700' : 'text-gray-600'">Debit</span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>

                                                {{-- Amount --}}
                                                <div class="form-group mb-4">
                                                    <label for="amount" class="form-label">Amount</label>
                                                    <div class="relative">
                                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">{{ currency_symbol() }}</span>
                                                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" max="999999.99" required class="form-input pl-8 text-lg font-semibold" placeholder="0.00">
                                                    </div>
                                                </div>

                                                {{-- Source --}}
                                                <div class="form-group mb-4">
                                                    <label for="source" class="form-label">Source</label>
                                                    <select id="source" name="source" required class="form-input">
                                                        <option value="">Select Source</option>
                                                        <optgroup label="Payment Gateways">
                                                            <option value="stripe">Stripe</option>
                                                            <option value="paypal">PayPal</option>
                                                            <option value="wise">Wise</option>
                                                            <option value="payoneer">Payoneer</option>
                                                        </optgroup>
                                                        <optgroup label="Bank Transfer">
                                                            <option value="bank_transfer">Bank Transfer</option>
                                                            <option value="wire_transfer">Wire Transfer</option>
                                                        </optgroup>
                                                        <optgroup label="Other">
                                                            <option value="cash">Cash</option>
                                                            <option value="cheque">Cheque</option>
                                                            <option value="crypto">Cryptocurrency</option>
                                                            <option value="bkash">bKash</option>
                                                            <option value="nagad">Nagad</option>
                                                            <option value="adjustment">Manual Adjustment</option>
                                                            <option value="refund">Refund</option>
                                                            <option value="bonus">Bonus/Promotion</option>
                                                            <option value="other">Other</option>
                                                        </optgroup>
                                                    </select>
                                                </div>

                                                {{-- Also adjust parent reseller --}}
                                                @if($user->isClient() && $user->parent && $user->parent->isReseller())
                                                    <div class="form-group mb-4">
                                                        <div class="flex items-start gap-3 p-3 rounded-lg border border-amber-200 bg-amber-50">
                                                            <input type="checkbox" name="adjust_reseller" value="1" id="adjustReseller"
                                                                   class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                            <label for="adjustReseller" class="text-sm">
                                                                <span class="font-medium text-gray-900" x-text="operation === 'credit' ? 'Also credit parent reseller' : 'Also debit parent reseller'"></span>
                                                                <span class="block text-gray-500 mt-0.5">{{ $user->parent->name }} — same amount will be applied</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Remarks --}}
                                                <div class="form-group mb-6">
                                                    <label for="remarks" class="form-label">Remarks (Optional)</label>
                                                    <textarea id="remarks" name="remarks" rows="2" class="form-input" placeholder="Transaction reference, notes..."></textarea>
                                                </div>

                                                {{-- Submit --}}
                                                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-white transition-colors"
                                                        :class="operation === 'credit' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-red-600 hover:bg-red-700'">
                                                    <span x-show="operation === 'credit'">Add Credit</span>
                                                    <span x-show="operation === 'debit'">Deduct Balance</span>
                                                </button>
                                            </form>
                                        </div>

                                        {{-- Right: Transaction History --}}
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-sm font-semibold text-gray-700">Recent Transactions</h4>
                                                <span class="text-xs text-gray-500">Last 15</span>
                                            </div>

                                            <div class="border border-gray-200 rounded-lg overflow-hidden max-h-80 overflow-y-auto">
                                                @if($recentTransactions->isEmpty())
                                                    <div class="p-8 text-center">
                                                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                        </svg>
                                                        <p class="text-sm text-gray-500">No transactions yet</p>
                                                    </div>
                                                @else
                                                    <table class="w-full text-sm">
                                                        <thead class="bg-gray-50 sticky top-0">
                                                            <tr>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Source</th>
                                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Amount</th>
                                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Balance</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            @foreach($recentTransactions as $txn)
                                                                <tr class="hover:bg-gray-50" title="{{ $txn->remarks }}">
                                                                    <td class="px-3 py-2 text-xs text-gray-500">{{ $txn->created_at->format('M d, H:i') }}</td>
                                                                    <td class="px-3 py-2">
                                                                        <span class="text-xs px-1.5 py-0.5 rounded {{ $txn->type === 'topup' ? 'bg-indigo-100 text-indigo-700' : ($txn->type === 'call_charge' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                                                                            {{ $txn->source ? ucfirst(str_replace('_', ' ', $txn->source)) : ucfirst(str_replace('_', ' ', $txn->type)) }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right font-mono text-xs {{ $txn->amount >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
                                                                        {{ $txn->amount >= 0 ? '+' : '' }}{{ format_currency(abs($txn->amount)) }}
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right font-mono text-xs text-gray-600">{{ format_currency($txn->balance_after) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif
                                            </div>

                                            <a href="{{ route('admin.transactions.index', ['user_id' => $user->id]) }}" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 flex items-center justify-center gap-1">
                                                View All Transactions
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(auth()->user()->isSuperAdmin() && !$user->isSuperAdmin())
                    <form action="{{ route('admin.impersonate.start', $user) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn-secondary text-amber-600 border-amber-300 hover:bg-amber-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Login As
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.users.edit', $user) }}" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                    @csrf
                    <button type="submit" class="{{ $user->status === 'active' ? 'btn-warning' : 'btn-success' }}">
                        @if($user->status === 'active')
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            Suspend
                        @else
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Activate
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value {{ $user->balance < 0 ? 'text-red-600' : '' }}">{{ format_currency($user->balance) }}</span>
                <span class="stat-label">Balance</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $user->sipAccounts->count() }}</span>
                <span class="stat-label">SIP Accounts</span>
            </div>
        </div>
        @if($isReseller)
            <div class="stat-card">
                <div class="stat-icon bg-sky-100 text-sky-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value">{{ $user->children->count() }}</span>
                    <span class="stat-label">Clients</span>
                </div>
            </div>
        @endif
        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $user->dids->count() ?? 0 }}</span>
                <span class="stat-label">DIDs</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100 text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $user->max_channels }}</span>
                <span class="stat-label">Max Channels</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Account Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Information</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Account Name</span>
                            <span class="detail-value">{{ $user->name }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email Address</span>
                            <span class="detail-value">{{ $user->email }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Account Type</span>
                            <span class="detail-value">
                                <span class="badge badge-{{ $isReseller ? 'purple' : ($isClient ? 'blue' : 'gray') }}">{{ ucfirst($user->role) }}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                @if($user->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($user->status === 'suspended')
                                    <span class="badge badge-warning">Suspended</span>
                                @else
                                    <span class="badge badge-danger">Disabled</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">KYC Status</span>
                            <span class="detail-value">
                                @if($user->kyc_status === 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($user->kyc_status === 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @elseif($user->kyc_status === 'rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                @else
                                    <span class="badge badge-gray">Not Submitted</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">{{ $user->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        @if($user->parent)
                            <div class="detail-item">
                                <span class="detail-label">Parent Account</span>
                                <span class="detail-value">
                                    @if($user->parent->isSuperAdmin())
                                        <span class="text-indigo-600 font-medium">Direct (Super Admin)</span>
                                    @else
                                        <a href="{{ route('admin.users.show', $user->parent) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $user->parent->name }}</a>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Billing Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Billing & Limits</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Billing Type</span>
                            <span class="detail-value">
                                @if($user->billing_type === 'prepaid')
                                    <span class="badge badge-blue">Prepaid</span>
                                @else
                                    <span class="badge badge-purple">Postpaid</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Current Balance</span>
                            <span class="detail-value font-semibold {{ $user->balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ format_currency($user->balance) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Credit Limit</span>
                            <span class="detail-value">{{ format_currency($user->credit_limit) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Rate Group / Tariff</span>
                            <span class="detail-value">{{ $user->rateGroup?->name ?? 'Not Assigned' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Max Channels</span>
                            <span class="detail-value">{{ $user->max_channels }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Daily Spend Limit</span>
                            <span class="detail-value">{{ $user->daily_spend_limit ? format_currency($user->daily_spend_limit) : 'Unlimited' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Daily Call Limit</span>
                            <span class="detail-value">{{ $user->daily_call_limit ?? 'Unlimited' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact --}}
            @if($user->phone || $user->contact_email || $user->address)
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Contact</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        @if($user->contact_email)
                            <div class="detail-item">
                                <span class="detail-label">Contact Email</span>
                                <span class="detail-value">{{ $user->contact_email }}</span>
                            </div>
                        @endif
                        @if($user->phone)
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value">{{ $user->phone }}</span>
                            </div>
                        @endif
                        @if($user->alt_phone)
                            <div class="detail-item">
                                <span class="detail-label">Alternative Phone</span>
                                <span class="detail-value">{{ $user->alt_phone }}</span>
                            </div>
                        @endif
                        @if($user->address)
                            <div class="detail-item">
                                <span class="detail-label">Address</span>
                                <span class="detail-value">
                                    {{ $user->address }}
                                    @if($user->city || $user->state)
                                        <br>{{ collect([$user->city, $user->state])->filter()->implode(', ') }}
                                    @endif
                                    @if($user->country || $user->zip_code)
                                        <br>{{ collect([$user->country, $user->zip_code])->filter()->implode(' ') }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Company Details --}}
            @if($user->company_name || $user->company_email || $user->company_website)
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Company Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        @if($user->company_name)
                            <div class="detail-item">
                                <span class="detail-label">Company Name</span>
                                <span class="detail-value">{{ $user->company_name }}</span>
                            </div>
                        @endif
                        @if($user->company_email)
                            <div class="detail-item">
                                <span class="detail-label">Company Email</span>
                                <span class="detail-value">{{ $user->company_email }}</span>
                            </div>
                        @endif
                        @if($user->company_website)
                            <div class="detail-item">
                                <span class="detail-label">Website</span>
                                <span class="detail-value"><a href="{{ $user->company_website }}" target="_blank" class="text-indigo-600 hover:text-indigo-700">{{ $user->company_website }}</a></span>
                            </div>
                        @endif
                        @if($user->notes)
                            <div class="detail-item" style="grid-column: span 2;">
                                <span class="detail-label">Notes</span>
                                <span class="detail-value">{{ $user->notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- KYC Information (Client only) --}}
            @if($isClient)
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">KYC Information</h3>
                    @if($user->kycProfile)
                        <a href="{{ route('admin.kyc.show', $user->kycProfile) }}" class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">View Full KYC</a>
                    @endif
                </div>
                <div class="detail-card-body">
                    @if($user->kycProfile)
                        @php $kyc = $user->kycProfile; @endphp
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">KYC Status</span>
                                <span class="detail-value">
                                    @if($user->kyc_status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($user->kyc_status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($user->kyc_status === 'rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                    @else
                                        <span class="badge badge-gray">{{ ucfirst($user->kyc_status ?? 'Not Submitted') }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Account Type</span>
                                <span class="detail-value">{{ ucfirst($kyc->account_type ?? '—') }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Account Name</span>
                                <span class="detail-value">{{ $kyc->full_name ?? '—' }}</span>
                            </div>
                            @if($kyc->contact_person)
                                <div class="detail-item">
                                    <span class="detail-label">Contact Person</span>
                                    <span class="detail-value">{{ $kyc->contact_person }}</span>
                                </div>
                            @endif
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value">{{ $kyc->phone ?? '—' }}</span>
                            </div>
                            @if($kyc->alt_phone)
                                <div class="detail-item">
                                    <span class="detail-label">Alt Phone</span>
                                    <span class="detail-value">{{ $kyc->alt_phone }}</span>
                                </div>
                            @endif
                            <div class="detail-item">
                                <span class="detail-label">Address</span>
                                <span class="detail-value">
                                    {{ $kyc->address_line1 ?? '' }}
                                    @if($kyc->address_line2) <br>{{ $kyc->address_line2 }} @endif
                                    @if($kyc->city || $kyc->state)
                                        <br>{{ collect([$kyc->city, $kyc->state])->filter()->implode(', ') }}
                                    @endif
                                    @if($kyc->country || $kyc->postal_code)
                                        <br>{{ collect([$kyc->country, $kyc->postal_code])->filter()->implode(' ') }}
                                    @endif
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">ID Type</span>
                                <span class="detail-value">{{ $kyc->id_type ? ucfirst(str_replace('_', ' ', $kyc->id_type)) : '—' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">ID Number</span>
                                <span class="detail-value font-mono">{{ $kyc->id_number ?? '—' }}</span>
                            </div>
                            @if($kyc->id_expiry_date)
                                <div class="detail-item">
                                    <span class="detail-label">ID Expiry</span>
                                    <span class="detail-value">{{ $kyc->id_expiry_date->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if($kyc->submitted_at)
                                <div class="detail-item">
                                    <span class="detail-label">Submitted</span>
                                    <span class="detail-value">{{ $kyc->submitted_at->format('d M Y, g:i A') }}</span>
                                </div>
                            @endif
                            @if($kyc->reviewed_at)
                                <div class="detail-item">
                                    <span class="detail-label">Reviewed</span>
                                    <span class="detail-value">{{ $kyc->reviewed_at->format('d M Y') }} by {{ $kyc->reviewer?->name ?? '—' }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- KYC Documents --}}
                        @if($kyc->documents->count() > 0)
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Documents</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    @foreach($kyc->documents as $doc)
                                        <a href="{{ route('admin.kyc.show', $kyc) }}" class="block p-3 bg-gray-50 rounded-lg hover:bg-indigo-50 transition-colors text-center group">
                                            <svg class="w-8 h-8 text-gray-400 group-hover:text-indigo-500 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <p class="text-xs text-gray-600 group-hover:text-indigo-700 font-medium">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Rejection reason --}}
                        @if($user->kyc_status === 'rejected' && $user->kyc_rejected_reason)
                            <div class="mt-4 p-3 bg-red-50 rounded-lg border border-red-200">
                                <p class="text-xs font-medium text-red-700">Rejection Reason</p>
                                <p class="text-sm text-red-600 mt-1">{{ $user->kyc_rejected_reason }}</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-6">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-sm text-gray-500">KYC not submitted yet</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- SIP Account Ranges (Reseller only) --}}
            @if($user->role === 'reseller')
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">SIP Account Ranges</h3>
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">Edit</a>
                </div>
                <div class="detail-card-body">
                    @if(!empty($user->sip_ranges))
                        <div class="space-y-2">
                            @foreach($user->sip_ranges as $range)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-mono font-medium text-gray-900">{{ $range['start'] }} — {{ $range['end'] }}</p>
                                            @php $rangeSize = (int)$range['end'] - (int)$range['start'] + 1; @endphp
                                            <p class="text-xs text-gray-400">{{ number_format($rangeSize) }} numbers</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500">No range restriction</p>
                            <p class="text-xs text-gray-400 mt-1">This reseller can create any SIP account number</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- SIP Accounts --}}
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">SIP Accounts</h3>
                    <span class="badge badge-gray">{{ $user->sipAccounts->count() }}</span>
                </div>
                @if($user->sipAccounts->isEmpty())
                    <div class="p-6 text-center">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <p class="text-sm text-gray-500">No SIP accounts</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($user->sipAccounts->take(5) as $sip)
                            <div class="px-4 py-2.5 flex items-center justify-between hover:bg-gray-50">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.sip-accounts.show', $sip) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $sip->username }}</a>
                                        <p class="text-xs text-gray-400">{{ $sip->caller_id_number ?? 'No Caller ID' }}</p>
                                    </div>
                                </div>
                                @if($sip->status === 'active')
                                    <span class="w-2 h-2 rounded-full bg-emerald-500" title="Active"></span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-gray-300" title="{{ ucfirst($sip->status) }}"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($user->sipAccounts->count() > 5)
                        <div class="px-4 py-2.5 bg-gray-50 text-center border-t border-gray-100">
                            <a href="{{ route('admin.sip-accounts.index', ['user_id' => $user->id]) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                View all {{ $user->sipAccounts->count() }} accounts →
                            </a>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('admin.cdr.index', ['user_id' => $user->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        View Call Records
                    </a>
                    <a href="{{ route('admin.transactions.index', ['user_id' => $user->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        View Transactions
                    </a>
                    <a href="{{ route('admin.invoices.index', ['user_id' => $user->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        View Invoices
                    </a>
                    @if($user->kyc_status === 'pending' && $user->kycProfile)
                        <a href="{{ route('admin.kyc.show', $user->kycProfile) }}" class="quick-action-btn text-amber-600 bg-amber-50 hover:bg-amber-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Review KYC
                        </a>
                    @endif
                </div>
            </div>

            {{-- Reseller: Clients List --}}
            @if($isReseller)
                <div class="detail-card">
                    <div class="detail-card-header flex items-center justify-between">
                        <h3 class="detail-card-title">Clients</h3>
                        <span class="badge badge-gray">{{ $user->children->count() }} clients</span>
                    </div>
                    @if($user->children->isEmpty())
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-gray-500">No clients yet</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach($user->children->take(5) as $child)
                                <div class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center">
                                            <span class="text-xs font-semibold text-sky-600">{{ strtoupper(substr($child->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.users.show', $child) }}" class="font-medium text-gray-900 hover:text-indigo-600 text-sm">{{ $child->name }}</a>
                                            <p class="text-xs text-gray-500">{{ format_currency($child->balance) }}</p>
                                        </div>
                                    </div>
                                    @if($child->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($child->status) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if($user->children->count() > 5)
                            <div class="px-5 py-3 bg-gray-50 text-center">
                                <a href="{{ route('admin.users.index', ['role' => 'client', 'parent_id' => $user->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    View all {{ $user->children->count() }} clients →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            {{-- DIDs --}}
            @if($user->dids && $user->dids->count() > 0)
                <div class="detail-card">
                    <div class="detail-card-header flex items-center justify-between">
                        <h3 class="detail-card-title">DIDs</h3>
                        <span class="badge badge-gray">{{ $user->dids->count() }} numbers</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach($user->dids->take(5) as $did)
                            <div class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                                <div>
                                    <a href="{{ route('admin.dids.show', $did) }}" class="font-mono font-medium text-gray-900 hover:text-indigo-600">{{ $did->number }}</a>
                                    <p class="text-xs text-gray-500">{{ format_currency($did->monthly_price) }}/mo</p>
                                </div>
                                @if($did->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warning">{{ ucfirst($did->status) }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($user->dids->count() > 5)
                        <div class="px-5 py-3 bg-gray-50 text-center">
                            <a href="{{ route('admin.dids.index', ['user_id' => $user->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                View all {{ $user->dids->count() }} DIDs →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Recent Activity / Last Login --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Activity</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Last Login</span>
                        <span class="text-gray-900">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Email Verified</span>
                        <span class="text-gray-900">
                            @if($user->email_verified_at)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-warning">No</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">2FA Enabled</span>
                        <span class="text-gray-900">
                            @if($user->two_factor_secret)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-gray">No</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
