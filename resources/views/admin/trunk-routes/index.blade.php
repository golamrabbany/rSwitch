<x-admin-layout>
    <x-slot name="header">Routing Rules</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Routing Rules</h2>
            <p class="page-subtitle">Manage trunk routing and failover configuration</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.trunk-routes.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Routing Rule
            </a>
        </div>
    </div>

    {{-- Route Test Tool --}}
    <div class="detail-card mb-6" x-data="routeTestTool()">
        <button @click="open = !open" type="button"
                class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 rounded-t-xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-semibold text-gray-900">Route Test Tool</span>
                    <p class="text-xs text-gray-500">Test which trunk would be selected for a destination</p>
                </div>
            </div>
            <svg :class="{ 'rotate-180': open }" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-cloak class="px-5 pb-5 border-t border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                <div class="form-group">
                    <label class="form-label">Destination Number</label>
                    <input type="text" x-model="destination" placeholder="e.g. 8801712345678"
                           class="form-input font-mono" @keydown.enter.prevent="testRoute()">
                </div>
                <div class="form-group">
                    <label class="form-label">Test Time (optional)</label>
                    <input type="datetime-local" x-model="testTime" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select x-model="testTimezone" class="form-input">
                        <option value="UTC">UTC</option>
                        <option value="Asia/Dhaka">Asia/Dhaka</option>
                        <option value="Europe/London">Europe/London</option>
                        <option value="America/New_York">America/New_York</option>
                        <option value="America/Los_Angeles">America/Los_Angeles</option>
                    </select>
                </div>
                <div class="form-group flex items-end">
                    <button @click="testRoute()" :disabled="loading || !destination" class="btn-primary w-full">
                        <span x-show="!loading">Test Route</span>
                        <span x-show="loading" x-cloak>Testing...</span>
                    </button>
                </div>
            </div>

            {{-- Results --}}
            <div x-show="result" x-cloak class="mt-6 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500">Matched Prefix:</span>
                    <template x-if="result && result.matched_prefix">
                        <span class="font-mono font-semibold text-indigo-600 text-lg" x-text="result.matched_prefix"></span>
                    </template>
                    <template x-if="result && !result.matched_prefix">
                        <span class="text-sm text-red-600">No matching route found</span>
                    </template>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="result && result.primary">
                    <div class="rounded-lg border-2 border-emerald-200 bg-emerald-50 p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="badge badge-success">Primary</span>
                        </div>
                        <template x-if="result && result.primary">
                            <div>
                                <p class="text-sm font-semibold text-gray-900" x-text="result.primary.trunk_name"></p>
                                <p class="text-xs text-gray-500" x-text="result.primary.provider"></p>
                                <div class="mt-2 text-xs text-gray-600 space-y-1">
                                    <p>Priority: <span class="font-medium" x-text="result.primary.priority"></span> | Weight: <span class="font-medium" x-text="result.primary.weight"></span></p>
                                    <p>Time: <span class="font-mono" x-text="result.primary.time_window"></span></p>
                                    <p>Days: <span x-text="result.primary.days"></span></p>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="rounded-lg border-2 p-4"
                         :class="result && result.failover ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50'">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="badge" :class="result && result.failover ? 'badge-warning' : 'badge-gray'">Failover</span>
                        </div>
                        <template x-if="result && result.failover">
                            <div>
                                <p class="text-sm font-semibold text-gray-900" x-text="result.failover.trunk_name"></p>
                                <p class="text-xs text-gray-500" x-text="result.failover.provider"></p>
                                <div class="mt-2 text-xs text-gray-600 space-y-1">
                                    <p>Priority: <span class="font-medium" x-text="result.failover.priority"></span> | Weight: <span class="font-medium" x-text="result.failover.weight"></span></p>
                                    <p>Time: <span class="font-mono" x-text="result.failover.time_window"></span></p>
                                    <p>Days: <span x-text="result.failover.days"></span></p>
                                </div>
                            </div>
                        </template>
                        <template x-if="result && !result.failover">
                            <p class="text-sm text-gray-400 italic">No failover trunk</p>
                        </template>
                    </div>
                </div>

                <div x-show="result && result.all_matches && result.all_matches.length > 0">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">All Matching Routes (<span x-text="result ? result.all_matches.length : 0"></span>)</h4>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trunk</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Weight</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="match in (result ? result.all_matches : [])" :key="match.id">
                                    <tr>
                                        <td class="px-3 py-2 text-gray-900" x-text="match.trunk_name"></td>
                                        <td class="px-3 py-2" x-text="match.priority"></td>
                                        <td class="px-3 py-2" x-text="match.weight"></td>
                                        <td class="px-3 py-2 font-mono text-xs" x-text="match.time_window"></td>
                                        <td class="px-3 py-2 text-xs" x-text="match.days"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="prefix" value="{{ request('prefix') }}" placeholder="Search by prefix..." class="filter-input font-mono">
            </div>

            <select name="trunk_id" class="filter-select">
                <option value="">All Trunks</option>
                @foreach ($trunks as $trunk)
                    <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>
                        {{ $trunk->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['prefix', 'trunk_id', 'status']))
                <a href="{{ route('admin.trunk-routes.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($routes->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Routing Rules Total : {{ number_format($routes->total()) }} &middot; Showing {{ $routes->firstItem() }} to {{ $routes->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prefix</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trunk</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time Window</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Days</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Weight</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($routes as $route)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group {{ !$loop->first && $route->prefix !== $routes[$loop->index - 1]->prefix ? '!border-t-2 !border-t-gray-300' : '' }}">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $routes->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <span class="font-mono font-medium text-gray-900">{{ $route->prefix }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div>
                                <a href="{{ route('admin.trunks.show', $route->trunk) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $route->trunk->name }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $route->trunk->provider }}</div>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            @if($route->time_start)
                                <span class="font-mono text-sm text-gray-900">
                                    {{ substr($route->time_start, 0, 5) }} - {{ substr($route->time_end, 0, 5) }}
                                </span>
                                <div class="text-xs text-gray-500">{{ $route->timezone }}</div>
                            @else
                                <span class="text-sm text-gray-400 italic">Always</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($route->days_of_week)
                                <span class="text-xs font-medium text-gray-700">{{ strtoupper($route->days_of_week) }}</span>
                            @else
                                <span class="text-sm text-gray-400">All</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium">{{ $route->priority }}</td>
                        <td class="px-3 py-2 font-medium">{{ $route->weight }}</td>
                        <td class="px-3 py-2">
                            @if($route->status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Disabled</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.trunk-routes.edit', $route) }}" class="p-1 rounded text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('admin.trunk-routes.destroy', $route) }}" class="inline"
                                      onsubmit="return confirm('Delete this routing rule?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                <p class="empty-text">No routing rules found</p>
                                <a href="{{ route('admin.trunk-routes.create') }}" class="empty-link-admin">Create your first routing rule</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($routes->hasPages())
        <div class="mt-6">
            {{ $routes->withQueryString()->links() }}
        </div>
    @endif

    <script>
    function routeTestTool() {
        return {
            open: false,
            destination: '',
            testTime: '',
            testTimezone: 'UTC',
            result: null,
            loading: false,
            async testRoute() {
                if (!this.destination) return;
                this.loading = true;
                this.result = null;
                try {
                    const response = await fetch('{{ route("admin.trunk-routes.test") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            destination: this.destination,
                            test_time: this.testTime || null,
                            test_timezone: this.testTimezone,
                        }),
                    });
                    this.result = await response.json();
                } catch (e) {
                    alert('Error testing route: ' + e.message);
                } finally {
                    this.loading = false;
                }
            }
        }
    }
    </script>
</x-admin-layout>
