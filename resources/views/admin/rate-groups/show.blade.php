<x-admin-layout>
    <x-slot name="header">Rate Group: {{ $rateGroup->name }}</x-slot>

    {{-- Group Info Card --}}
    <div class="bg-white shadow sm:rounded-lg p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $rateGroup->name }}</h2>
                    @if($rateGroup->type === 'admin')
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">Admin</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">Reseller</span>
                    @endif
                </div>
                @if($rateGroup->description)
                    <p class="text-sm text-gray-600 mb-3">{{ $rateGroup->description }}</p>
                @endif
                <div class="flex items-center gap-6 text-sm text-gray-500">
                    <span>Rates: <strong class="text-gray-900">{{ number_format($rateGroup->rates_count) }}</strong></span>
                    <span>Users: <strong class="text-gray-900">{{ number_format($rateGroup->users_count) }}</strong></span>
                    <span>Created by: <strong class="text-gray-900">{{ $rateGroup->creator?->name ?? '—' }}</strong></span>
                    @if($rateGroup->parentRateGroup)
                        <span>Parent: <a href="{{ route('admin.rate-groups.show', $rateGroup->parentRateGroup) }}" class="text-indigo-600 hover:text-indigo-500">{{ $rateGroup->parentRateGroup->name }}</a></span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.rate-groups.edit', $rateGroup) }}"
                   class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Edit
                </a>
                <form method="POST" action="{{ route('admin.rate-groups.destroy', $rateGroup) }}"
                      onsubmit="return confirm('Delete this rate group and all its rates?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Action Bar --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.rate-groups.rates.create', $rateGroup) }}"
           class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Rate
        </a>
        <a href="{{ route('admin.rate-groups.export', $rateGroup) }}"
           class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Export CSV
        </a>
    </div>

    {{-- CSV Import Section --}}
    <div class="mb-6 bg-white shadow sm:rounded-lg" x-data="{ open: false }">
        <button @click="open = !open" class="w-full flex items-center justify-between p-4 text-left">
            <span class="text-sm font-semibold text-gray-900">Import Rates from CSV</span>
            <svg :class="{ 'rotate-180': open }" class="h-5 w-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="border-t border-gray-200 p-4">
            <form method="POST" action="{{ route('admin.rate-groups.import', $rateGroup) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="file" class="block text-xs font-medium text-gray-500 mb-1">CSV File</label>
                        <input type="file" id="file" name="file" accept=".csv,.txt" required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error :messages="$errors->get('file')" class="mt-1" />
                    </div>
                    <div>
                        <label for="mode" class="block text-xs font-medium text-gray-500 mb-1">Import Mode</label>
                        <select id="mode" name="mode" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="merge">Merge (update existing, add new)</option>
                            <option value="add_only">Add Only (skip existing prefixes)</option>
                            <option value="replace">Replace (delete all, import fresh)</option>
                        </select>
                    </div>
                    <div>
                        <label for="effective_date" class="block text-xs font-medium text-gray-500 mb-1">Effective Date</label>
                        <input type="date" id="effective_date" name="effective_date" value="{{ now()->format('Y-m-d') }}" required
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Import
                    </button>
                    <p class="text-xs text-gray-500">Required columns: prefix, destination, rate_per_minute. Optional: connection_fee, min_duration, billing_increment, end_date, status</p>
                </div>
            </form>

            @if($recentImports->isNotEmpty())
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Recent Imports</h4>
                    <div class="space-y-1">
                        @foreach($recentImports as $imp)
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $imp->file_name }} by {{ $imp->uploader?->name }}</span>
                                <span>
                                    {{ $imp->imported_rows }} imported, {{ $imp->skipped_rows }} skipped, {{ $imp->error_rows }} errors
                                    @if($imp->status === 'completed')
                                        <span class="text-green-600">completed</span>
                                    @elseif($imp->status === 'failed')
                                        <span class="text-red-600">failed</span>
                                    @else
                                        <span class="text-yellow-600">{{ $imp->status }}</span>
                                    @endif
                                    — {{ $imp->created_at->diffForHumans() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Rates Filter --}}
    <div class="mb-4 bg-white shadow sm:rounded-lg p-4">
        <form method="GET" action="{{ route('admin.rate-groups.show', $rateGroup) }}">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label for="prefix" class="block text-xs font-medium text-gray-500 mb-1">Prefix</label>
                    <input type="text" id="prefix" name="prefix" value="{{ request('prefix') }}"
                           placeholder="e.g. 1201..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="destination" class="block text-xs font-medium text-gray-500 mb-1">Destination</label>
                    <input type="text" id="destination" name="destination" value="{{ request('destination') }}"
                           placeholder="e.g. United States..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select id="status" name="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Filter
                    </button>
                    @if(request()->hasAny(['prefix', 'destination', 'status']))
                        <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Rates Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prefix</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rate/Min</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Conn. Fee</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Min Dur</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Increment</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effective</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($rates as $rate)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900">{{ $rate->prefix }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $rate->destination }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">${{ number_format($rate->rate_per_minute, 6) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">${{ number_format($rate->connection_fee, 6) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">{{ $rate->min_duration }}s</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">{{ $rate->billing_increment }}s</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $rate->effective_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $rate->end_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($rate->status === 'active')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">Disabled</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.rate-groups.rates.edit', [$rateGroup, $rate]) }}" class="text-indigo-600 hover:text-indigo-500">Edit</a>
                                <form method="POST" action="{{ route('admin.rate-groups.rates.destroy', [$rateGroup, $rate]) }}"
                                      onsubmit="return confirm('Delete this rate?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-500">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-sm text-gray-500">
                            No rates in this group. <a href="{{ route('admin.rate-groups.rates.create', $rateGroup) }}" class="text-indigo-600 hover:text-indigo-500">Add a rate</a> or import from CSV.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rates->hasPages())
        <div class="mt-4">
            {{ $rates->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
