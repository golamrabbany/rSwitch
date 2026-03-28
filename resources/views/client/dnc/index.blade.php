<x-client-layout>
    <x-slot name="header">DNC List</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Do Not Call List</h2>
            <p class="page-subtitle">Manage phone numbers blocked from your broadcasts</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="form-card">
                <div class="form-card-header">
                    <h3 class="form-card-title">Add Numbers</h3>
                    <p class="form-card-subtitle">Block numbers from your broadcasts</p>
                </div>
                <div class="form-card-body">
                    <form method="POST" action="{{ route('client.dnc.store') }}" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Phone Numbers</label>
                            <textarea name="phone_numbers" rows="6" required class="form-input font-mono text-sm" placeholder="Enter numbers, one per line&#10;&#10;8801712345678&#10;8801898765432">{{ old('phone_numbers') }}</textarea>
                            <p class="form-hint">Separate with new lines, commas, or semicolons.</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reason <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="reason" value="{{ old('reason') }}" class="form-input" placeholder="e.g. Customer opted out">
                        </div>
                        <button type="submit" class="btn-action-primary-admin w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Add to DNC List
                        </button>
                    </form>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Summary</h3></div>
                <div class="detail-card-body">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center py-1 border-b border-gray-100">
                            <span class="text-gray-500">Total Blocked</span>
                            <span class="font-semibold text-gray-900">{{ number_format($totalCount) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-500">Showing</span>
                            <span class="font-medium text-gray-700">{{ $numbers->count() }} of {{ number_format($numbers->total()) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="filter-card mb-3">
                <form method="GET" class="filter-row">
                    <div class="filter-search-box">
                        <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone number..." class="filter-input">
                    </div>
                    <button type="submit" class="btn-search-admin">Search</button>
                    @if(request('search'))
                        <a href="{{ route('client.dnc.index') }}" class="btn-clear">Clear</a>
                    @endif
                </form>
            </div>

            <div x-data="{ selected: [], selectAll: false }" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                @if($numbers->total() > 0)
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            DNC Numbers : {{ number_format($numbers->total()) }} &middot; Showing {{ $numbers->firstItem() }} to {{ $numbers->lastItem() }}
                        </span>
                        <form x-show="selected.length > 0" x-cloak method="POST" action="{{ route('client.dnc.bulk-destroy') }}" onsubmit="return confirm('Remove selected numbers?')">
                            @csrf
                            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Remove <span x-text="selected.length"></span> selected
                            </button>
                        </form>
                    </div>
                @endif
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2 text-left" style="width: 40px;">
                                <input type="checkbox" x-model="selectAll" @change="selected = selectAll ? {{ json_encode($numbers->pluck('id')->toArray()) }} : []" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone Number</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($numbers as $number)
                            <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                                <td class="px-3 py-2"><input type="checkbox" :value="{{ $number->id }}" x-model="selected" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                                <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $numbers->firstItem() + $loop->index }}</td>
                                <td class="px-3 py-2 font-mono font-semibold text-gray-900">{{ $number->phone_number }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $number->reason ?: '—' }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $number->created_at->format('d M Y') }}</td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                                        <form method="POST" action="{{ route('client.dnc.destroy', $number) }}" onsubmit="return confirm('Remove this number?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-700 hover:bg-red-50 transition-colors" title="Remove">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <p class="text-sm text-gray-400">{{ request('search') ? 'No numbers matching "' . request('search') . '"' : 'No numbers in DNC list yet' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($numbers->hasPages())
                <div class="mt-4 flex justify-end">{{ $numbers->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</x-client-layout>
