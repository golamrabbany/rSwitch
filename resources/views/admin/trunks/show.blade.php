<x-admin-layout>
    <x-slot name="header">Trunk Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $trunk->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @if($trunk->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @elseif($trunk->status === 'auto_disabled')
                        <span class="badge badge-warning">Auto-disabled</span>
                    @else
                        <span class="badge badge-danger">Disabled</span>
                    @endif
                    @if($provisioned)
                        <span class="badge badge-info">Provisioned</span>
                    @else
                        <span class="badge badge-danger">Not Provisioned</span>
                    @endif
                    @if($trunk->direction === 'outgoing')
                        <span class="badge badge-success">Outgoing</span>
                    @elseif($trunk->direction === 'incoming')
                        <span class="badge badge-info">Incoming</span>
                    @else
                        <span class="badge badge-purple">Both</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="page-actions">
            <form method="POST" action="{{ route('admin.trunks.reprovision', $trunk) }}" class="inline">
                @csrf
                <button type="submit" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Re-provision
                </button>
            </form>
            <a href="{{ route('admin.trunks.edit', $trunk) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <form method="POST" action="{{ route('admin.trunks.destroy', $trunk) }}" class="inline" onsubmit="return confirm('Delete this trunk? This will also remove it from rSwitch.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Trunk Configuration --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Trunk Configuration</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Name</span>
                            <span class="detail-value">{{ $trunk->name }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Provider</span>
                            <span class="detail-value">{{ $trunk->provider }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Host</span>
                            <span class="detail-value font-mono">{{ $trunk->host }}:{{ $trunk->port }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Transport</span>
                            <span class="detail-value">{{ strtoupper($trunk->transport) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Max Channels</span>
                            <span class="detail-value">{{ $trunk->max_channels }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Rate Group</span>
                            <span class="detail-value">
                                @if($trunk->rateGroup)
                                    <span class="text-indigo-600 font-medium">{{ $trunk->rateGroup->name }}</span>
                                @else
                                    <span class="text-gray-400">None</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">Codecs</span>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                @foreach(explode(',', $trunk->codec_allow) as $codec)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-indigo-50 border border-indigo-200 text-xs font-mono font-medium text-indigo-700">{{ trim($codec) }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Authentication (outgoing/both only) --}}
            @if(in_array($trunk->direction, ['outgoing', 'both']))
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Authentication</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Username</span>
                            <span class="detail-value font-mono">{{ $trunk->username ?: '-' }}</span>
                        </div>
                        @if($trunk->password)
                        <div class="detail-item">
                            <span class="detail-label">Password</span>
                            <div x-data="{ show: false }" class="flex items-center gap-2">
                                <span x-show="!show" class="detail-value text-gray-400">••••••••••••</span>
                                <span x-show="show" x-cloak class="detail-value font-mono">{{ $trunk->password }}</span>
                                <button @click="show = !show" class="text-xs text-indigo-600 hover:text-indigo-800" x-text="show ? 'Hide' : 'Show'"></button>
                            </div>
                        </div>
                        @endif
                        <div class="detail-item">
                            <span class="detail-label">Registration</span>
                            <span class="detail-value">
                                @if($trunk->register)
                                    <span class="badge badge-success">Enabled</span>
                                @else
                                    <span class="text-gray-500">Disabled</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Incoming Settings (incoming/both) --}}
            @if(in_array($trunk->direction, ['incoming', 'both']))
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Incoming Settings</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Incoming Context</span>
                            <span class="detail-value font-mono">{{ $trunk->incoming_context }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Auth Type</span>
                            <span class="detail-value">{{ ucfirst($trunk->incoming_auth_type) }}</span>
                        </div>
                        @if($trunk->incoming_ip_acl)
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">IP ACL</span>
                            <span class="detail-value font-mono">{{ $trunk->incoming_ip_acl }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Dial String Manipulation (outgoing/both) --}}
            @if(in_array($trunk->direction, ['outgoing', 'both']))
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Dial String Manipulation</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Pattern Match</span>
                            <span class="detail-value font-mono">{{ $trunk->dial_pattern_match ?: '-' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pattern Replace</span>
                            <span class="detail-value font-mono">{{ $trunk->dial_pattern_replace ?: '-' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Dial Prefix</span>
                            <span class="detail-value font-mono">{{ $trunk->dial_prefix ?: '-' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Strip Digits</span>
                            <span class="detail-value">{{ $trunk->dial_strip_digits ?? 0 }}</span>
                        </div>
                        @if($trunk->tech_prefix)
                        <div class="detail-item">
                            <span class="detail-label">Tech Prefix</span>
                            <span class="detail-value font-mono">{{ $trunk->tech_prefix }}</span>
                        </div>
                        @endif
                        <div class="detail-item">
                            <span class="detail-label">CLI Mode</span>
                            <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $trunk->cli_mode)) }}</span>
                        </div>
                        @if($trunk->cli_mode === 'override' && $trunk->cli_override_number)
                        <div class="detail-item">
                            <span class="detail-label">CLI Override</span>
                            <span class="detail-value font-mono">{{ $trunk->cli_override_number }}</span>
                        </div>
                        @endif
                        @if($trunk->cli_mode === 'prefix_strip')
                        <div class="detail-item">
                            <span class="detail-label">CLI Strip/Add</span>
                            <span class="detail-value font-mono">-{{ $trunk->cli_prefix_strip ?? 0 }} / +{{ $trunk->cli_prefix_add ?: 'none' }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Routing Rules (outgoing/both) --}}
            @if(in_array($trunk->direction, ['outgoing', 'both']))
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">Routing Rules</h3>
                    <a href="{{ route('admin.trunk-routes.create', ['trunk_id' => $trunk->id]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        + Add Route
                    </a>
                </div>
                @if($trunk->routes->isEmpty())
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">No routing rules configured.</span>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Prefix</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Priority</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Weight</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($trunk->routes->sortBy(['prefix', 'priority']) as $route)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-mono">{{ $route->prefix }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        @if($route->time_start)
                                            <span class="font-mono">{{ substr($route->time_start, 0, 5) }} - {{ substr($route->time_end, 0, 5) }}</span>
                                        @else
                                            <span class="text-gray-400">Always</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">{{ $route->priority }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $route->weight }}</td>
                                    <td class="px-4 py-2">
                                        @if($route->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Disabled</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-100">
                        <a href="{{ route('admin.trunk-routes.index', ['trunk_id' => $trunk->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                            View all routing rules &rarr;
                        </a>
                    </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Provisioning Status --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">rSwitch Status</h3>
                </div>
                <div class="detail-card-body">
                    @if($provisioned)
                        <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-emerald-800">Provisioned</p>
                                <p class="text-xs text-emerald-600">Active in PJSIP config</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-red-800">Not Provisioned</p>
                                <p class="text-xs text-red-600">Not in PJSIP config</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Health check removed — was causing trunk auto-disable issues --}}

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    @if(in_array($trunk->direction, ['outgoing', 'both']))
                    <a href="{{ route('admin.trunk-routes.create', ['trunk_id' => $trunk->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Routing Rule
                    </a>
                    @endif
                    <a href="{{ route('admin.cdr.index', ['trunk_id' => $trunk->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        View Call Records
                    </a>
                </div>
            </div>

            {{-- Notes --}}
            @if($trunk->notes)
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Notes</h3>
                </div>
                <div class="detail-card-body">
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $trunk->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('admin.trunks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Trunks
        </a>
    </div>
</x-admin-layout>
