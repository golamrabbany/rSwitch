<x-admin-layout>
    <x-slot name="header">Trunk Routing Rules</x-slot>

    {{-- Route Test Tool --}}
    <div class="mb-6" x-data="routeTestTool()">
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <button @click="open = !open" type="button"
                    class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span class="text-base font-semibold text-gray-900">Route Test Tool</span>
                </div>
                <svg :class="{ 'rotate-180': open }" class="h-5 w-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="px-6 pb-6 border-t border-gray-200">
                <p class="mt-4 text-sm text-gray-500 mb-4">Enter a destination number to see which trunk(s) would be selected.</p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Destination Number</label>
                        <input type="text" x-model="destination" placeholder="e.g. 8801712345678"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                               @keydown.enter.prevent="testRoute()">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Test Time (optional)</label>
                        <input type="datetime-local" x-model="testTime"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Timezone</label>
                        <select x-model="testTimezone"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="UTC">UTC</option>
                            <option value="Asia/Dhaka">Asia/Dhaka</option>
                            <option value="Europe/London">Europe/London</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="America/Los_Angeles">America/Los_Angeles</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button @click="testRoute()" :disabled="loading || !destination"
                                class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!loading">Test Route</span>
                            <span x-show="loading" x-cloak>Testing...</span>
                        </button>
                    </div>
                </div>

                {{-- Results --}}
                <div x-show="result" x-cloak class="mt-6 space-y-4">
                    {{-- Matched prefix --}}
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-500">Matched Prefix:</span>
                        <template x-if="result && result.matched_prefix">
                            <span class="font-mono font-semibold text-indigo-600 text-lg" x-text="result.matched_prefix"></span>
                        </template>
                        <template x-if="result && !result.matched_prefix">
                            <span class="text-sm text-red-600">No matching route found</span>
                        </template>
                    </div>

                    {{-- Primary & Failover cards --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="result && result.primary">
                        <div class="rounded-lg border-2 border-green-300 bg-green-50 p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Primary</span>
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
                             :class="result && result.failover ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200 bg-gray-50'">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                      :class="result && result.failover ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'">Failover</span>
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

                    {{-- All matches table --}}
                    <div x-show="result && result.all_matches && result.all_matches.length > 0">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">All Matching Routes (<span x-text="result ? result.all_matches.length : 0"></span>)</h4>
                        <div class="overflow-hidden rounded-md border border-gray-200">
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
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input type="text" name="prefix" value="{{ request('prefix') }}" placeholder="Filter by prefix..."
                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-40 font-mono">
            <select name="trunk_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Trunks</option>
                @foreach ($trunks as $trunk)
                    <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>
                        {{ $trunk->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>
            <button type="submit" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Search</button>
            @if(request()->hasAny(['prefix', 'trunk_id', 'status']))
                <a href="{{ route('admin.trunk-routes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
        <a href="{{ route('admin.trunk-routes.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Create Routing Rule
        </a>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prefix</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trunk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Window</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($routes as $route)
                    <tr class="{{ !$loop->first && $route->prefix !== $routes[$loop->index - 1]->prefix ? 'border-t-2 border-gray-300' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-medium text-gray-900">{{ $route->prefix }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.trunks.show', $route->trunk) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                {{ $route->trunk->name }}
                            </a>
                            <div class="text-xs text-gray-500">{{ $route->trunk->provider }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($route->time_start)
                                <span class="text-sm font-mono text-gray-900">
                                    {{ substr($route->time_start, 0, 5) }} - {{ substr($route->time_end, 0, 5) }}
                                </span>
                                <div class="text-xs text-gray-500">{{ $route->timezone }}</div>
                            @else
                                <span class="text-sm text-gray-400 italic">Always</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($route->days_of_week)
                                <span class="text-xs font-medium text-gray-700">{{ strtoupper($route->days_of_week) }}</span>
                            @else
                                <span class="text-sm text-gray-400">All</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $route->priority }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $route->weight }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $route->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($route->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="{{ route('admin.trunk-routes.edit', $route) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <form method="POST" action="{{ route('admin.trunk-routes.destroy', $route) }}" class="inline"
                                  onsubmit="return confirm('Delete this routing rule?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">No routing rules found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $routes->withQueryString()->links() }}
    </div>

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
