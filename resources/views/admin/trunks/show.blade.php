<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>Trunk: {{ $trunk->name }}</span>
            <div class="flex items-center gap-x-3">
                <a href="{{ route('admin.trunks.edit', $trunk) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Edit</a>
                <form method="POST" action="{{ route('admin.trunks.reprovision', $trunk) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-gray-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-500">
                        Re-provision
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.trunks.destroy', $trunk) }}" onsubmit="return confirm('Delete this trunk? This will also remove it from Asterisk PJSIP config.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Delete</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Trunk Configuration --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Trunk Configuration</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->name }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Provider</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->provider }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Direction</dt>
                    <dd class="mt-1 sm:col-span-2 sm:mt-0">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $trunk->direction === 'outgoing' ? 'bg-green-100 text-green-800' : ($trunk->direction === 'incoming' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                            {{ ucfirst($trunk->direction) }}
                        </span>
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Host</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->host }}:{{ $trunk->port }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Transport</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ strtoupper($trunk->transport) }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Codecs</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->codec_allow }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Max Channels</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->max_channels }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 sm:col-span-2 sm:mt-0">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $trunk->status === 'active' ? 'bg-green-100 text-green-800' : ($trunk->status === 'auto_disabled' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                            {{ $trunk->status === 'auto_disabled' ? 'Auto-disabled' : ucfirst($trunk->status) }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Auth & Provisioning --}}
        <div class="space-y-6">
            @if(in_array($trunk->direction, ['outgoing', 'both']))
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Authentication</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Username</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->username ?: '-' }}</dd>
                    </div>
                    @if($trunk->password)
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Password</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0" x-data="{ show: false }">
                            <span x-show="!show" class="text-gray-400">••••••••••••</span>
                            <span x-show="show" x-cloak class="font-mono text-gray-900">{{ $trunk->password }}</span>
                            <button @click="show = !show" class="ml-2 text-xs text-indigo-600 hover:text-indigo-900" x-text="show ? 'Hide' : 'Show'"></button>
                        </dd>
                    </div>
                    @endif
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Registration</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if($trunk->register)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Enabled</span>
                                @if($trunk->register_string)
                                    <span class="ml-2 text-xs font-mono text-gray-500">{{ $trunk->register_string }}</span>
                                @endif
                            @else
                                <span class="text-gray-500">Disabled</span>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Outgoing Priority</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->outgoing_priority }}</dd>
                    </div>
                </dl>
            </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Provisioning Status</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        @if($provisioned)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                                </svg>
                                Provisioned in Asterisk
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                                </svg>
                                Not in Asterisk
                            </span>
                        @endif
                    </div>

                    {{-- Health info --}}
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500">Health:</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $trunk->health_status === 'up' ? 'bg-green-100 text-green-800' : ($trunk->health_status === 'down' ? 'bg-red-100 text-red-800' : ($trunk->health_status === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($trunk->health_status) }}
                            </span>
                        </div>
                        @if($trunk->health_last_checked_at)
                            <p class="text-sm text-gray-600">Last checked: {{ $trunk->health_last_checked_at->format('M d, Y H:i:s') }}</p>
                        @endif
                        @if($trunk->health_last_up_at)
                            <p class="text-sm text-gray-600">Last up: {{ $trunk->health_last_up_at->format('M d, Y H:i:s') }}</p>
                        @endif
                        @if($trunk->health_fail_count > 0)
                            <p class="text-sm text-red-600">Consecutive failures: {{ $trunk->health_fail_count }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Full-width sections --}}
    <div class="mt-6 space-y-6">
        {{-- Dial Manipulation (outgoing/both only) --}}
        @if(in_array($trunk->direction, ['outgoing', 'both']))
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Dial String Manipulation</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Pattern Match</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->dial_pattern_match ?: '-' }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Pattern Replace</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->dial_pattern_replace ?: '-' }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Dial Prefix</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->dial_prefix ?: '-' }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Strip Digits</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->dial_strip_digits }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Tech Prefix</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->tech_prefix ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        {{-- CLI Manipulation --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">CLI / Caller ID Manipulation</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">CLI Mode</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst(str_replace('_', ' ', $trunk->cli_mode)) }}</dd>
                </div>
                @if($trunk->cli_mode === 'override')
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Override Number</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->cli_override_number ?: '-' }}</dd>
                </div>
                @endif
                @if($trunk->cli_mode === 'prefix_strip')
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Strip Digits</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->cli_prefix_strip }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Add Prefix</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->cli_prefix_add ?: '-' }}</dd>
                </div>
                @endif
            </dl>
        </div>
        @endif

        {{-- Incoming Settings (incoming/both only) --}}
        @if(in_array($trunk->direction, ['incoming', 'both']))
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Incoming Settings</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Incoming Context</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->incoming_context }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Auth Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst($trunk->incoming_auth_type) }}</dd>
                </div>
                @if($trunk->incoming_ip_acl)
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">IP ACL</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->incoming_ip_acl }}</dd>
                </div>
                @endif
            </dl>
        </div>
        @endif

        {{-- Health Monitoring --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Health Monitoring</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Health Check</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        @if($trunk->health_check)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Enabled</span>
                        @else
                            <span class="text-gray-500">Disabled</span>
                        @endif
                    </dd>
                </div>
                @if($trunk->health_check)
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Check Interval</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->health_check_interval }}s</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Auto-disable Threshold</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->health_auto_disable_threshold }} consecutive failures</dd>
                </div>
                @if($trunk->health_asr_threshold)
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">ASR Threshold</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $trunk->health_asr_threshold }}%</dd>
                </div>
                @endif
                @endif
            </dl>
        </div>

        {{-- Notes --}}
        @if($trunk->notes)
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Notes</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $trunk->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.trunks.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">&larr; Back to trunks</a>
    </div>
</x-admin-layout>
