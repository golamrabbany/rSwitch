<x-admin-layout>
    <x-slot name="header">Routing Rules</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Routing Rules</h2>
            <p class="page-subtitle">Manage trunk routing and failover configuration</p>
        </div>
        <div class="page-actions">
            <button onclick="document.dispatchEvent(new CustomEvent('open-route-test'))" type="button" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Test Route
            </button>
            <button type="button" onclick="document.dispatchEvent(new CustomEvent('open-route-form', {detail: {mode: 'create'}}))" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Routing Rule
            </button>
        </div>
    </div>

    {{-- Route Test Modal --}}
    <div x-data="routeTestTool()" @open-route-test.document="open = true">
        {{-- Modal --}}
        <div x-show="open" x-cloak class="relative z-50" role="dialog" aria-modal="true" @keydown.escape.window="open = false">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-start justify-center p-4 pt-16" @click="open = false">
                    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         @click.stop class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] flex flex-col">

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Route Test Tool</h3>
                                    <p class="text-xs text-gray-500">Test which trunk would be selected for a destination</p>
                                </div>
                            </div>
                            <button @click="open = false" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="px-6 py-5 overflow-y-auto flex-1">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Destination Number</label>
                                    <input type="text" x-model="destination" placeholder="e.g. 01712345678"
                                           class="form-input font-mono" @keydown.enter.prevent="testRoute()" x-ref="destInput">
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
                            <div x-show="result" x-cloak class="mt-5 space-y-4 border-t border-gray-100 pt-5">
                                {{-- Matched Info --}}
                                {{-- Transformation Steps --}}
                                <template x-if="result && result.primary">
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-2 mb-3">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Number Transformation</p>
                                            <template x-if="result.primary.mnp_enabled">
                                                <span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-medium">MNP</span>
                                            </template>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <div class="flex items-end gap-2 font-mono text-xs min-w-max">
                                                {{-- Original --}}
                                                <div class="text-center">
                                                    <p class="text-[10px] text-gray-400 font-sans mb-1">Original</p>
                                                    <span class="inline-block bg-white border border-gray-200 px-2.5 py-1.5 rounded-md text-gray-800 font-semibold" x-text="result.primary.step_original"></span>
                                                </div>

                                                {{-- Remove step --}}
                                                <template x-if="result.primary.step_after_remove">
                                                    <div class="flex items-end gap-2">
                                                        <svg class="w-4 h-4 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                        <div class="text-center">
                                                            <p class="text-[10px] text-red-400 font-sans mb-1">Remove <span class="font-mono">"<span x-text="result.primary.remove_prefix"></span>"</span></p>
                                                            <span class="inline-block bg-red-50 border border-red-200 px-2.5 py-1.5 rounded-md text-red-700 font-semibold" x-text="result.primary.step_after_remove"></span>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Add step --}}
                                                <template x-if="result.primary.step_after_add">
                                                    <div class="flex items-end gap-2">
                                                        <svg class="w-4 h-4 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                        <div class="text-center">
                                                            <p class="text-[10px] text-blue-400 font-sans mb-1">Add <span class="font-mono">"<span x-text="result.primary.add_prefix"></span>"</span></p>
                                                            <span class="inline-block bg-blue-50 border border-blue-200 px-2.5 py-1.5 rounded-md text-blue-700 font-semibold" x-text="result.primary.step_after_add"></span>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- MNP step --}}
                                                <template x-if="result.primary.step_after_mnp">
                                                    <div class="flex items-end gap-2">
                                                        <svg class="w-4 h-4 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                        <div class="text-center">
                                                            <p class="text-[10px] text-purple-400 font-sans mb-1">MNP</p>
                                                            <span class="inline-block bg-purple-50 border border-purple-200 px-2.5 py-1.5 rounded-md text-purple-700 font-semibold" x-text="result.primary.step_after_mnp"></span>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Final Output --}}
                                                <div class="flex items-end gap-2">
                                                    <svg class="w-4 h-4 text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    <div class="text-center">
                                                        <p class="text-[10px] text-emerald-500 font-sans mb-1 font-bold">Sent to Trunk</p>
                                                        <span class="inline-block bg-emerald-50 border-2 border-emerald-400 px-3 py-1.5 rounded-md text-emerald-700 font-bold text-sm" x-text="result.primary.destination_number"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="result && !result.matched_prefix">
                                    <div class="p-4 bg-red-50 rounded-lg text-center">
                                        <span class="text-sm text-red-600 font-medium">No matching route found</span>
                                    </div>
                                </template>

                                {{-- Primary & Failover --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="result && result.primary">
                                    <div class="rounded-lg border-2 border-emerald-200 bg-emerald-50 p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="badge badge-success">Primary</span>
                                        </div>
                                        <template x-if="result && result.primary">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900" x-text="result.primary.trunk_name"></p>
                                                <p class="text-xs text-gray-500" x-text="result.primary.provider"></p>
                                                <div class="mt-2 font-mono text-sm text-emerald-700 bg-emerald-100/50 px-2 py-1 rounded" x-text="result.primary.destination_number"></div>
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
                                                <div class="mt-2 font-mono text-sm text-amber-700 bg-amber-100/50 px-2 py-1 rounded" x-text="result.failover.destination_number"></div>
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

                                {{-- All Matching Routes --}}
                                <div x-show="result && result.all_matches && result.all_matches.length > 0">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">All Matching Routes (<span x-text="result ? result.all_matches.length : 0"></span>)</h4>
                                    <div class="overflow-hidden rounded-lg border border-gray-200">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trunk</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
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
                                                        <td class="px-3 py-2 font-mono text-xs text-gray-700" x-text="match.destination_number"></td>
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

                        {{-- Footer --}}
                        <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                            <button @click="open = false" class="btn-secondary">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Routing Rule Add/Edit Modal --}}
    <div x-data="routeFormModal()"
         @open-route-form.document="openForm($event.detail)"
         x-init="autoOpenFromQuery()">
        <div x-show="open" x-cloak class="relative z-50" role="dialog" aria-modal="true" @keydown.escape.window="close()">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-start justify-center p-4 pt-10" @click="close()">
                    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         @click.stop class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900" x-text="mode === 'edit' ? 'Edit Routing Rule' : 'Add Routing Rule'"></h3>
                                    <p class="text-xs text-gray-500" x-text="mode === 'edit' ? 'Update trunk routing configuration' : 'Define a new trunk routing rule'"></p>
                                </div>
                            </div>
                            <button @click="close()" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="px-6 py-5 overflow-y-auto flex-1">
                            {{-- Loading overlay (edit fetch) --}}
                            <div x-show="loading" x-cloak class="text-center py-8 text-sm text-gray-500">Loading routing rule...</div>

                            <form x-show="!loading" @submit.prevent="submit()" class="space-y-6">
                                {{-- Route Target --}}
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Route Target</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">Trunk</label>
                                            <div class="relative">
                                                <input type="text"
                                                       x-model="trunkSearch"
                                                       @focus="showTrunkDropdown = true"
                                                       @click="showTrunkDropdown = true"
                                                       @input="showTrunkDropdown = true; form.trunk_id = ''"
                                                       @keydown.escape.stop="showTrunkDropdown = false"
                                                       class="form-input pr-10"
                                                       placeholder="Type to search trunks..."
                                                       autocomplete="off">
                                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                    <template x-if="form.trunk_id">
                                                        <button type="button" @click="clearTrunk()" class="text-gray-400 hover:text-gray-600">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </template>
                                                    <template x-if="!form.trunk_id">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                        </svg>
                                                    </template>
                                                </div>
                                                <div x-show="showTrunkDropdown" x-cloak x-transition
                                                     @click.outside="showTrunkDropdown = false"
                                                     class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                                    <template x-if="filteredTrunks.length === 0">
                                                        <div class="px-4 py-3 text-sm text-gray-500">No trunks found</div>
                                                    </template>
                                                    <template x-for="trunk in filteredTrunks" :key="trunk.id">
                                                        <button type="button" @click="selectTrunk(trunk)"
                                                                class="w-full px-4 py-2.5 text-left hover:bg-indigo-50 flex items-center justify-between transition-colors"
                                                                :class="{ 'bg-indigo-50': form.trunk_id == trunk.id }">
                                                            <div>
                                                                <span class="font-medium text-gray-900" x-text="trunk.name"></span>
                                                                <span class="text-gray-500 ml-1" x-text="'(' + trunk.provider + ')'"></span>
                                                            </div>
                                                            <span class="text-xs px-2 py-0.5 rounded-full"
                                                                  :class="trunk.direction === 'outgoing' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                                                                  x-text="trunk.direction"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                            <p class="form-hint">Only outgoing/both trunks are shown.</p>
                                            <p x-show="errors.trunk_id" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.trunk_id?.[0]"></p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Destination Prefix</label>
                                            <input type="text" x-model="form.prefix" required class="form-input font-mono" placeholder="e.g. 88017, 44, 1">
                                            <p class="form-hint">Numeric digits only. Longer prefix = more specific match.</p>
                                            <p x-show="errors.prefix" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.prefix?.[0]"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Priority & Load Balancing --}}
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Priority &amp; Load Balancing</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">Priority</label>
                                            <input type="number" x-model.number="form.priority" required min="1" max="100" class="form-input">
                                            <p class="form-hint">Lower = higher priority</p>
                                            <p x-show="errors.priority" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.priority?.[0]"></p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Weight</label>
                                            <input type="number" x-model.number="form.weight" required min="1" max="1000" class="form-input">
                                            <p class="form-hint">Load balancing weight</p>
                                            <p x-show="errors.weight" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.weight?.[0]"></p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Status</label>
                                            <select x-model="form.status" required class="form-input">
                                                <option value="active">Active</option>
                                                <option value="disabled">Disabled</option>
                                            </select>
                                            <p x-show="errors.status" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.status?.[0]"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Dial Manipulation --}}
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Dial Manipulation</h4>
                                    <div class="p-3 bg-amber-50 rounded-lg border border-amber-100 mb-3">
                                        <p class="text-xs text-amber-700 font-mono">Remove Prefix → Add Prefix → MNP Dipping → Trunk Manipulation</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">Remove Prefix</label>
                                            <input type="text" x-model="form.remove_prefix" class="form-input font-mono" placeholder="e.g. 880" maxlength="20">
                                            <p class="form-hint">Strip from the beginning of the dialed number</p>
                                            <p x-show="errors.remove_prefix" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.remove_prefix?.[0]"></p>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Add Prefix</label>
                                            <input type="text" x-model="form.add_prefix" class="form-input font-mono" placeholder="e.g. 0" maxlength="20">
                                            <p class="form-hint">Prepend to the dialed number (after removal)</p>
                                            <p x-show="errors.add_prefix" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.add_prefix?.[0]"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- BD MNP --}}
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">BD MNP</h4>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" x-model="form.mnp_enabled" class="form-checkbox">
                                        <span class="text-sm text-gray-700">Enable BD MNP auto-conversion</span>
                                    </label>
                                    <p class="form-hint">Auto-converts BD mobile numbers to MNP format. Non-BD numbers pass through unchanged.</p>
                                    <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <p class="text-xs font-medium text-gray-600 mb-2">Operator Routing Map</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-mono">
                                            <span class="text-gray-500">013,017 <span class="text-indigo-600">→ 71</span> <span class="text-gray-400">GP</span></span>
                                            <span class="text-gray-500">014,019 <span class="text-indigo-600">→ 91</span> <span class="text-gray-400">BL</span></span>
                                            <span class="text-gray-500">015 <span class="text-indigo-600">→ 51</span> <span class="text-gray-400">TT</span></span>
                                            <span class="text-gray-500">016,018 <span class="text-indigo-600">→ 81</span> <span class="text-gray-400">Robi</span></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Time-Based Routing --}}
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Time-Based Routing</h4>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" x-model="timeBasedRouting" class="form-checkbox">
                                        <span class="text-sm text-gray-700">Enable time window restriction</span>
                                    </label>
                                    <p class="form-hint">Leave unchecked for routes that are always active.</p>

                                    <div x-show="timeBasedRouting" x-cloak class="space-y-4 mt-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="form-group">
                                                <label class="form-label">Start Time</label>
                                                <input type="time" x-model="form.time_start" class="form-input">
                                                <p class="form-hint">Inclusive (e.g. 06:00)</p>
                                                <p x-show="errors.time_start" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.time_start?.[0]"></p>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">End Time</label>
                                                <input type="time" x-model="form.time_end" class="form-input">
                                                <p class="form-hint">Exclusive (e.g. 18:00)</p>
                                                <p x-show="errors.time_end" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.time_end?.[0]"></p>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Timezone</label>
                                                <select x-model="form.timezone" class="form-input">
                                                    <option value="UTC">UTC</option>
                                                    <option value="Asia/Dhaka">Asia/Dhaka (UTC+6)</option>
                                                    <option value="Europe/London">Europe/London</option>
                                                    <option value="Europe/Berlin">Europe/Berlin</option>
                                                    <option value="America/New_York">America/New_York</option>
                                                    <option value="America/Chicago">America/Chicago</option>
                                                    <option value="America/Los_Angeles">America/Los_Angeles</option>
                                                    <option value="Asia/Kolkata">Asia/Kolkata (UTC+5:30)</option>
                                                    <option value="Asia/Singapore">Asia/Singapore (UTC+8)</option>
                                                    <option value="Australia/Sydney">Australia/Sydney</option>
                                                </select>
                                                <p x-show="errors.timezone" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.timezone?.[0]"></p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="flex items-center gap-2">
                                                <input type="checkbox" x-model="dayRestriction" class="form-checkbox">
                                                <span class="text-sm text-gray-700">Restrict to specific days</span>
                                            </label>
                                        </div>
                                        <div x-show="dayRestriction" x-cloak>
                                            <label class="form-label">Days of Week</label>
                                            <div class="flex flex-wrap gap-4 mt-2">
                                                <template x-for="day in [{v:'mon',l:'Mon'},{v:'tue',l:'Tue'},{v:'wed',l:'Wed'},{v:'thu',l:'Thu'},{v:'fri',l:'Fri'},{v:'sat',l:'Sat'},{v:'sun',l:'Sun'}]" :key="day.v">
                                                    <label class="flex items-center gap-1.5">
                                                        <input type="checkbox" :value="day.v" x-model="form.days" class="form-checkbox">
                                                        <span class="text-sm text-gray-700" x-text="day.l"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Generic error --}}
                                <div x-show="errors.message" x-cloak class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" x-text="errors.message"></div>
                            </form>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button @click="close()" type="button" class="btn-secondary" :disabled="saving">Cancel</button>
                            <button @click="submit()" type="button" class="btn-primary" :disabled="saving || loading">
                                <span x-show="!saving" x-text="mode === 'edit' ? 'Save Changes' : 'Create Routing Rule'"></span>
                                <span x-show="saving" x-cloak>Saving...</span>
                            </button>
                        </div>
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
                                <div class="text-xs text-gray-500 font-mono">{{ $route->trunk->host }}@if($route->mnp_enabled) <span class="text-purple-600 font-sans font-medium ml-1">MNP</span>@endif</div>
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
                                <button type="button" onclick="document.dispatchEvent(new CustomEvent('open-route-form', {detail: {mode: 'edit', id: {{ $route->id }}}}))" class="p-1 rounded text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
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
                                <button type="button" onclick="document.dispatchEvent(new CustomEvent('open-route-form', {detail: {mode: 'create'}}))" class="empty-link-admin">Create your first routing rule</button>
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

    @php
        $trunksForModal = $trunks->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'provider' => $t->provider,
            'direction' => $t->direction,
        ])->values();
    @endphp
    <script>
    function routeFormModal() {
        const trunks = {!! $trunksForModal->toJson() !!};
        const defaults = () => ({
            trunk_id: '',
            prefix: '',
            priority: 1,
            weight: 100,
            status: 'active',
            remove_prefix: '',
            add_prefix: '',
            mnp_enabled: false,
            time_start: '',
            time_end: '',
            timezone: 'Asia/Dhaka',
            days: [],
        });
        return {
            open: false,
            mode: 'create',
            routeId: null,
            loading: false,
            saving: false,
            errors: {},
            trunks: trunks,
            trunkSearch: '',
            showTrunkDropdown: false,
            timeBasedRouting: false,
            dayRestriction: false,
            form: defaults(),

            get filteredTrunks() {
                if (!this.trunkSearch) return this.trunks;
                const s = this.trunkSearch.toLowerCase();
                return this.trunks.filter(t => t.name.toLowerCase().includes(s) || t.provider.toLowerCase().includes(s));
            },
            selectTrunk(t) {
                this.form.trunk_id = t.id;
                this.trunkSearch = t.name + ' (' + t.provider + ')';
                this.showTrunkDropdown = false;
            },
            clearTrunk() {
                this.form.trunk_id = '';
                this.trunkSearch = '';
            },
            setTrunkSearchFromId(id) {
                const t = this.trunks.find(x => x.id == id);
                this.trunkSearch = t ? (t.name + ' (' + t.provider + ')') : '';
            },
            resetForm(prefillTrunkId = null) {
                this.form = defaults();
                this.timeBasedRouting = false;
                this.dayRestriction = false;
                this.errors = {};
                this.trunkSearch = '';
                this.routeId = null;
                if (prefillTrunkId) {
                    this.form.trunk_id = parseInt(prefillTrunkId, 10);
                    this.setTrunkSearchFromId(this.form.trunk_id);
                }
            },
            populateForm(d) {
                this.form = {
                    trunk_id: d.trunk_id,
                    prefix: d.prefix,
                    priority: d.priority,
                    weight: d.weight,
                    status: d.status,
                    remove_prefix: d.remove_prefix || '',
                    add_prefix: d.add_prefix || '',
                    mnp_enabled: !!d.mnp_enabled,
                    time_start: d.time_start ? String(d.time_start).substring(0, 5) : '',
                    time_end: d.time_end ? String(d.time_end).substring(0, 5) : '',
                    timezone: d.timezone || 'UTC',
                    days: d.days_of_week ? String(d.days_of_week).split(',') : [],
                };
                this.timeBasedRouting = !!d.time_start;
                this.dayRestriction = !!d.days_of_week;
                this.setTrunkSearchFromId(d.trunk_id);
                this.errors = {};
            },
            async openForm(detail) {
                this.errors = {};
                if (detail.mode === 'edit') {
                    this.mode = 'edit';
                    this.routeId = detail.id;
                    this.open = true;
                    this.loading = true;
                    try {
                        const r = await fetch(`/admin/trunk-routes/${detail.id}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!r.ok) throw new Error('Failed to load');
                        const data = await r.json();
                        this.populateForm(data);
                    } catch (e) {
                        alert('Failed to load route: ' + e.message);
                        this.close();
                    } finally {
                        this.loading = false;
                    }
                } else {
                    this.mode = 'create';
                    this.resetForm(detail.trunk_id || null);
                    this.open = true;
                }
            },
            autoOpenFromQuery() {
                const params = new URLSearchParams(window.location.search);
                if (params.get('add') === '1') {
                    this.openForm({ mode: 'create', trunk_id: params.get('trunk_id') });
                }
            },
            close() {
                if (this.saving) return;
                this.open = false;
                this.showTrunkDropdown = false;
            },
            async submit() {
                if (this.saving || this.loading) return;
                this.saving = true;
                this.errors = {};
                const url = this.mode === 'edit'
                    ? `/admin/trunk-routes/${this.routeId}`
                    : '{{ route("admin.trunk-routes.store") }}';
                const method = this.mode === 'edit' ? 'PUT' : 'POST';
                const payload = {
                    trunk_id: this.form.trunk_id,
                    prefix: this.form.prefix,
                    priority: this.form.priority,
                    weight: this.form.weight,
                    status: this.form.status,
                    remove_prefix: this.form.remove_prefix || '',
                    add_prefix: this.form.add_prefix || '',
                    mnp_enabled: this.form.mnp_enabled ? 1 : 0,
                    time_start: this.timeBasedRouting ? (this.form.time_start || '') : '',
                    time_end: this.timeBasedRouting ? (this.form.time_end || '') : '',
                    timezone: this.timeBasedRouting ? (this.form.timezone || 'UTC') : 'UTC',
                    days: (this.timeBasedRouting && this.dayRestriction) ? this.form.days : [],
                };
                try {
                    const r = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });
                    if (r.status === 422) {
                        const data = await r.json();
                        this.errors = data.errors || { message: data.message || 'Validation failed' };
                        return;
                    }
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    const data = await r.json();
                    window.location.href = data.redirect || '{{ route("admin.trunk-routes.index") }}';
                } catch (e) {
                    this.errors = { message: 'Failed to save: ' + e.message };
                } finally {
                    this.saving = false;
                }
            },
        };
    }

    function routeTestTool() {
        return {
            open: false,
            init() {
                this.$watch('open', (val) => { if (val) this.$nextTick(() => this.$refs.destInput?.focus()); });
            },
            destination: '',
            testTime: '',
            testTimezone: 'Asia/Dhaka',
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
