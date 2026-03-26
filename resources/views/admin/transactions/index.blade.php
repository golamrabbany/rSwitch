<x-admin-layout>
    <x-slot name="header">Transactions</x-slot>

    {{-- Section 1: Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Transactions</h2>
            <p class="page-subtitle">View all financial transactions across the platform</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.balance.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Adjust Balance
            </a>
        </div>
    </div>

    {{-- Section 2: Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..." class="filter-input">
            </div>

            {{-- User auto-suggest --}}
            <div class="relative" x-data="{
                open: false,
                search: '{{ $users->firstWhere('id', request('user_id'))?->name ?? '' }}',
                selectedId: '{{ request('user_id') }}',
                users: {{ $users->toJson() }},
                get filtered() {
                    if (!this.search) return this.users.slice(0, 20);
                    const s = this.search.toLowerCase();
                    return this.users.filter(u => u.name.toLowerCase().includes(s) || u.email.toLowerCase().includes(s)).slice(0, 20);
                },
                select(u) { this.search = u.name; this.selectedId = u.id; this.open = false; },
                clear() { this.search = ''; this.selectedId = ''; }
            }" @click.outside="open = false" @keydown.escape="open = false">
                <input type="hidden" name="user_id" :value="selectedId">
                <div class="relative">
                    <input type="text" x-model="search"
                           @focus="open = true"
                           @input="open = true; selectedId = ''"
                           placeholder="Filter by user..."
                           class="filter-input pr-9"
                           :class="selectedId ? 'border-indigo-500 ring-1 ring-indigo-500' : ''"
                           autocomplete="off">
                    <button type="button" x-show="search" @click="clear()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div x-show="open && filtered.length > 0" x-transition x-cloak
                     class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto" style="min-width: 280px;">
                    <template x-for="u in filtered" :key="u.id">
                        <button type="button" @click="select(u)"
                                class="w-full px-4 py-2 text-left hover:bg-indigo-50 flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium flex-shrink-0"
                                 :class="u.role === 'reseller' ? 'bg-emerald-100 text-emerald-600' : 'bg-sky-100 text-sky-600'"
                                 x-text="u.name.charAt(0).toUpperCase()"></div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 truncate" x-text="u.name"></div>
                                <div class="text-xs text-gray-500 truncate" x-text="u.email"></div>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                                  :class="u.role === 'reseller' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'"
                                  x-text="u.role.charAt(0).toUpperCase() + u.role.slice(1)"></span>
                        </button>
                    </template>
                </div>
            </div>

            <select name="type" class="filter-select">
                <option value="">All Types</option>
                @foreach (['topup', 'daily_call_charge', 'daily_reseller_charge', 'client_payment', 'did_charge', 'refund', 'adjustment', 'invoice_payment'] as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $t)) }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-select" title="From Date">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-select" title="To Date">

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['user_id', 'type', 'date_from', 'date_to', 'search']))
                <a href="{{ route('admin.transactions.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Section 3: Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {{-- Summary Bar --}}
        @if($transactions->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Total : {{ number_format($transactions->total()) }} &middot; Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }}
                    &middot; Credits: {{ format_currency($stats['total_credits'] ?? 0) }}
                    &middot; Debits: {{ format_currency($stats['total_debits'] ?? 0) }}
                </span>
            </div>
        @endif

        {{-- Table --}}
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $transactions->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <span class="text-gray-800">{{ $txn->created_at->format('M d, Y') }}</span>
                            <span class="block text-xs text-gray-400">{{ $txn->created_at->format('H:i:s') }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <a href="{{ route('admin.users.show', $txn->user_id) }}" class="font-semibold text-gray-800 group-hover:text-indigo-600 transition-colors">
                                {{ $txn->user?->name ?? '—' }}
                            </a>
                            <span class="block text-xs text-gray-400">{{ ucfirst($txn->user?->role ?? '') }}</span>
                        </td>
                        <td class="px-3 py-2">
                            @php
                                $typeColor = match($txn->type) {
                                    'topup', 'client_payment' => 'badge-success',
                                    'daily_call_charge', 'daily_reseller_charge' => 'badge-danger',
                                    'call_charge', 'reseller_call_charge' => 'badge-danger',
                                    'refund' => 'badge-warning',
                                    'adjustment' => 'badge-info',
                                    default => 'badge-gray',
                                };
                            @endphp
                            <span class="badge {{ $typeColor }}">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</span>
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ Str::limit($txn->description, 40) }}</td>
                        <td class="px-3 py-2 text-right">
                            @if($txn->amount >= 0)
                                <span class="font-bold text-emerald-600 tabular-nums">+{{ format_currency($txn->amount, 4) }}</span>
                            @else
                                <span class="font-bold text-red-600 tabular-nums">{{ format_currency($txn->amount, 4) }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            <span class="font-bold text-gray-900 tabular-nums">{{ format_currency($txn->balance_after, 4) }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.transactions.show', $txn) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-sm text-gray-400">No transactions found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $transactions->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-admin-layout>
