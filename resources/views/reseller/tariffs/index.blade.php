<x-reseller-layout>
    <x-slot name="header">Rate Groups</x-slot>

    <div x-data="rateGroupPage()" x-cloak>
        {{-- Page Header --}}
        <div class="page-header-row">
            <div>
                <h2 class="page-title">My Rate Groups</h2>
                <p class="page-subtitle">Manage rate groups and pricing for your clients</p>
            </div>
            <div class="page-actions">
                <button @click="openCreate()" class="btn-action-primary-reseller">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Rate Group
                </button>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="filter-card mb-3">
            <form method="GET" class="filter-row flex-wrap">
                <div class="filter-search-box">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search rate group name..." class="filter-input">
                </div>
                <button type="submit" class="btn-search-reseller">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('reseller.tariffs.index') }}" class="btn-clear">Clear</a>
                @endif
            </form>
        </div>

        {{-- Data Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            @if($rateGroups->total() > 0)
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Rate Groups Total : {{ number_format($rateGroups->total()) }} &middot; Showing {{ $rateGroups->firstItem() }} to {{ $rateGroups->lastItem() }}
                    </span>
                </div>
            @endif
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate Group</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Parent (Base)</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Rates</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Clients</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rateGroups as $group)
                        <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-emerald-50/50 transition-all border-b border-gray-100 group">
                            <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $rateGroups->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $group->name }}</div>
                                    @if($group->description)
                                        <div class="text-xs text-gray-500">{{ Str::limit($group->description, 40) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                @if($group->parentRateGroup)
                                    <span class="text-gray-700">{{ $group->parentRateGroup->name }}</span>
                                @else
                                    <span class="text-gray-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="font-semibold text-gray-900">{{ number_format($group->rates_count) }}</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="font-semibold text-gray-900">{{ number_format($group->users_count) }}</span>
                            </td>
                            <td class="px-3 py-2 text-gray-500">{{ $group->created_at?->format('M d, Y') }}</td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('reseller.tariffs.show', $group) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <button @click="openEdit({{ json_encode(['id' => $group->id, 'name' => $group->name, 'description' => $group->description]) }})" class="p-1.5 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="openDelete({{ $group->id }}, '{{ addslashes($group->name) }}', {{ $group->users_count }})" class="p-1.5 rounded-lg text-red-400 hover:text-red-700 hover:bg-red-50 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-sm text-gray-400">No rate groups found</p>
                                <button @click="openCreate()" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium mt-1">Create your first rate group</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rateGroups->hasPages())
            <div class="mt-4 flex justify-end">
                {{ $rateGroups->withQueryString()->links() }}
            </div>
        @endif

        {{-- Create/Edit Rate Group Modal --}}
        <div x-show="showModal" x-cloak class="relative z-50" @keydown.escape.window="showModal = false">
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="showModal = false">
                    <div x-show="showModal"
                         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-lg" @click.stop>

                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="mode === 'create' ? 'bg-emerald-100' : 'bg-amber-100'">
                                    <template x-if="mode === 'create'">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </template>
                                    <template x-if="mode === 'edit'">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900" x-text="mode === 'create' ? 'Create Rate Group' : 'Edit Rate Group'"></h3>
                                    <p class="text-sm text-gray-500" x-text="mode === 'create' ? 'Add a new rate group for clients' : 'Update rate group details'"></p>
                                </div>
                            </div>
                            <button @click="showModal = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form :action="mode === 'create' ? '{{ route('reseller.tariffs.store') }}' : '{{ url('reseller/tariffs') }}/' + editId" method="POST">
                            @csrf
                            <template x-if="mode === 'edit'">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <div class="px-6 py-5 space-y-4">
                                <div>
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" x-model="form.name" required placeholder="e.g., Premium Rates" class="form-input">
                                    <p class="text-xs text-gray-400 mt-1">A descriptive name for this rate group</p>
                                </div>
                                <div>
                                    <label class="form-label">Description</label>
                                    <textarea name="description" x-model="form.description" rows="2" placeholder="Optional description" class="form-input"></textarea>
                                </div>

                                <template x-if="mode === 'create'">
                                    <div class="space-y-4">
                                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-xs text-gray-500"><span class="font-medium text-gray-700">Base:</span> {{ auth()->user()->rateGroup?->name ?? 'None' }} ({{ auth()->user()->rateGroup?->rates()->where('status', 'active')->count() ?? 0 }} rates)</p>
                                        </div>
                                        <label class="flex items-start gap-3">
                                            <input type="checkbox" name="copy_rates" value="1" checked class="w-4 h-4 mt-0.5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">Copy rates from base</span>
                                                <p class="text-xs text-gray-400">Start with all base rates</p>
                                            </div>
                                        </label>
                                        <div>
                                            <label class="form-label">Markup (%)</label>
                                            <input type="number" name="markup_percent" value="20" min="0" max="500" class="form-input w-32">
                                            <p class="text-xs text-gray-400 mt-1">% above base rates</p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                                <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
                                <button type="submit" class="btn-primary-reseller">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="mode === 'create' ? 'Create' : 'Save Changes'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="deleteModal" x-cloak class="relative z-50" @keydown.escape.window="deleteModal = false">
            <div x-show="deleteModal"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="deleteModal = false">
                    <div x-show="deleteModal"
                         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-md" @click.stop>

                        <div class="p-6 text-center">
                            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Rate Group</h3>
                            <p class="text-sm text-gray-500 mb-1">Are you sure you want to delete this rate group?</p>
                            <p class="text-sm font-medium text-gray-700" x-text="deleteName"></p>
                            <template x-if="deleteClients > 0">
                                <p class="text-sm text-red-600 mt-2 font-medium" x-text="deleteClients + ' client(s) are assigned — cannot delete.'"></p>
                            </template>
                            <template x-if="deleteClients === 0">
                                <p class="text-xs text-gray-400 mt-3">All rates in this group will be permanently removed.</p>
                            </template>
                        </div>

                        <div class="flex items-center gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <button type="button" @click="deleteModal = false" class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                            <template x-if="deleteClients === 0">
                                <form :action="'{{ url('reseller/tariffs') }}/' + deleteId" method="POST" class="flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete</button>
                                </form>
                            </template>
                            <template x-if="deleteClients > 0">
                                <button type="button" disabled class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-300 rounded-lg cursor-not-allowed">Delete</button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
function rateGroupPage() {
    return {
        showModal: false,
        mode: 'create',
        editId: null,
        form: { name: '', description: '' },
        deleteModal: false,
        deleteId: null,
        deleteName: '',
        deleteClients: 0,
        openCreate() {
            this.mode = 'create';
            this.editId = null;
            this.form = { name: '', description: '' };
            this.showModal = true;
        },
        openEdit(data) {
            this.mode = 'edit';
            this.editId = data.id;
            this.form = { name: data.name, description: data.description || '' };
            this.showModal = true;
        },
        openDelete(id, name, clients) {
            this.deleteId = id;
            this.deleteName = name;
            this.deleteClients = clients;
            this.deleteModal = true;
        }
    }
}
</script>
@endpush
</x-reseller-layout>
