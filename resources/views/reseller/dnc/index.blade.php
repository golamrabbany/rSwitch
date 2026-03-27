<x-reseller-layout>
    <x-slot name="header">DNC List</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Do Not Call List</h2>
            <p class="page-subtitle">{{ number_format($totalCount) }} numbers blocked from broadcasts</p>
        </div>
    </div>

    {{-- Add Numbers --}}
    <div class="form-card mb-4">
        <div class="form-card-header">
            <h3 class="form-card-title">Add Numbers</h3>
            <p class="form-card-subtitle">Add phone numbers to your DNC list</p>
        </div>
        <div class="form-card-body">
            <form method="POST" action="{{ route('reseller.dnc.store') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Phone Numbers</label>
                        <textarea name="phone_numbers" rows="3" required class="form-input font-mono" placeholder="Enter numbers (one per line, or comma-separated)">{{ old('phone_numbers') }}</textarea>
                        <p class="form-hint">Supports multiple formats: one per line, comma or semicolon separated</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason (optional)</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-input" placeholder="e.g. Customer requested">
                        <p class="form-hint">Why these numbers are blocked</p>
                        <button type="submit" class="btn-primary mt-3 w-full" style="background: #059669;">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Add to DNC
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Filter --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search number..." class="filter-input">
            </div>
            <button type="submit" class="btn-search-reseller">Search</button>
            @if(request('search'))
                <a href="{{ route('reseller.dnc.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($numbers->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Total : {{ number_format($numbers->total()) }} &middot; Showing {{ $numbers->firstItem() }}–{{ $numbers->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone Number</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Added</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($numbers as $number)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-emerald-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $numbers->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2 font-mono font-semibold text-gray-900">{{ $number->phone_number }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $number->reason ?: '—' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $number->created_at->format('d M Y') }}</td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('reseller.dnc.destroy', $number) }}" class="inline" onsubmit="return confirm('Remove this number from DNC?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-700 hover:bg-red-50 transition-colors opacity-60 group-hover:opacity-100" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            <p class="text-sm text-gray-400">No DNC numbers yet</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($numbers->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $numbers->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-reseller-layout>
