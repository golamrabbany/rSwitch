{{-- CDR Archive Restore Banner — Include on any page that queries call_records --}}
@if(auth()->user()->isSuperAdmin())
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-3 flex items-center justify-between" x-data="{
        showRestore: false,
        month: '',
        loading: false
    }">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm text-amber-800">Live CDR: last <strong>30 days</strong>. Need older data?</span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showRestore = !showRestore" type="button"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-700 px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition-colors">
                Load Archive
            </button>
            <div x-show="showRestore" x-cloak class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.cdr.restore-archive') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="month" name="month" x-model="month" required class="filter-select text-sm" max="{{ now()->subMonth()->format('Y-m') }}">
                    <button type="submit" :disabled="!month || loading" @click="loading = true"
                            class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        <span x-show="!loading">Restore</span>
                        <span x-show="loading">Restoring...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
