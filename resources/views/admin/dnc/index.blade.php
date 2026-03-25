<x-admin-layout>
    <x-slot name="header">DNC List</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Do Not Call List</h2>
            <p class="page-subtitle">Manage phone numbers blocked from broadcasts</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-red-100">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalCount) }}</p>
                <p class="stat-label">Total Blocked Numbers</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Add Numbers Form --}}
        <div class="lg:col-span-1">
            <div class="form-card">
                <div class="form-card-header">
                    <h3 class="form-card-title">Add Numbers</h3>
                    <p class="form-card-subtitle">Add one or more numbers to DNC list</p>
                </div>
                <div class="form-card-body">
                    <form method="POST" action="{{ route('admin.dnc.store') }}" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Phone Numbers</label>
                            <textarea name="phone_numbers" rows="6" required class="form-input" placeholder="Enter numbers, one per line or comma-separated&#10;&#10;e.g.&#10;8801712345678&#10;8801898765432">{{ old('phone_numbers') }}</textarea>
                            <p class="form-hint">Separate with new lines, commas, or semicolons. Min 7 digits each.</p>
                            @error('phone_numbers') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Reason <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="reason" value="{{ old('reason') }}" class="form-input" placeholder="e.g. Customer requested opt-out">
                            @error('reason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-action-primary-admin w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Add to DNC List
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Numbers List --}}
        <div class="lg:col-span-2">
            <div class="form-card">
                <div class="form-card-header">
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h3 class="form-card-title">Blocked Numbers</h3>
                            <p class="form-card-subtitle">{{ number_format($numbers->total()) }} numbers in DNC list</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-input !py-1.5 !text-sm" placeholder="Search number..." style="width: 200px;">
                            <button type="submit" class="btn-action-primary-admin !py-1.5 !text-sm">Search</button>
                            @if(request('search'))
                                <a href="{{ route('admin.dnc.index') }}" class="btn-action-secondary !py-1.5 !text-sm">Clear</a>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="form-card-body p-0">
                    @if($numbers->count())
                        <div x-data="{ selected: [], selectAll: false }" class="overflow-x-auto">
                            @if($numbers->total() > 0)
                                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                        DNC Numbers Total : {{ number_format($numbers->total()) }} &middot; Showing {{ $numbers->firstItem() }} to {{ $numbers->lastItem() }}
                                    </span>
                                </div>
                            @endif
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" style="width: 40px;">
                                            <input type="checkbox" x-model="selectAll" @change="selected = selectAll ? {{ json_encode($numbers->pluck('id')->toArray()) }} : []" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone Number</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Added By</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date Added</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($numbers as $dnc)
                                        <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                                            <td class="px-3 py-2">
                                                <input type="checkbox" :value="{{ $dnc->id }}" x-model="selected" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            </td>
                                            <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $numbers->firstItem() + $loop->index }}</td>
                                            <td class="px-3 py-2 font-mono font-medium">{{ $dnc->phone_number }}</td>
                                            <td class="px-3 py-2 text-gray-500 text-sm">{{ $dnc->reason ?: '—' }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $dnc->addedBy?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-500">{{ $dnc->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                                    <form method="POST" action="{{ route('admin.dnc.destroy', $dnc) }}" onsubmit="return confirm('Remove this number from DNC list?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors" title="Remove">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {{-- Bulk Delete --}}
                            <div x-show="selected.length > 0" x-cloak class="px-4 py-3 bg-gray-50 border-t flex items-center justify-between">
                                <span class="text-sm text-gray-600" x-text="selected.length + ' number(s) selected'"></span>
                                <form method="POST" action="{{ route('admin.dnc.bulk-destroy') }}" onsubmit="return confirm('Remove selected numbers from DNC list?')">
                                    @csrf
                                    <template x-for="id in selected" :key="id">
                                        <input type="hidden" name="ids[]" :value="id">
                                    </template>
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">
                                        Remove Selected
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="px-4 py-3 border-t">
                            {{ $numbers->withQueryString()->links('vendor.pagination.tailwind') }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-gray-500 text-sm">
                                @if(request('search'))
                                    No numbers matching "{{ request('search') }}"
                                @else
                                    No numbers in DNC list yet
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
