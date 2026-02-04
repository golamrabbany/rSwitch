<x-reseller-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>SIP Account: {{ $sipAccount->username }}</span>
            <div class="flex items-center gap-x-3">
                <a href="{{ route('reseller.sip-accounts.edit', $sipAccount) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Edit</a>
                <form method="POST" action="{{ route('reseller.sip-accounts.reprovision', $sipAccount) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-gray-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-500">
                        Re-provision
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- SIP Details --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">SIP Configuration</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Username</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $sipAccount->username }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Password</dt>
                    <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0" x-data="{ show: false }">
                        <span x-show="!show" class="text-gray-400">••••••••••••</span>
                        <span x-show="show" x-cloak class="font-mono text-gray-900">{{ $sipAccount->password }}</span>
                        <button @click="show = !show" class="ml-2 text-xs text-indigo-600 hover:text-indigo-900" x-text="show ? 'Hide' : 'Show'"></button>
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Auth Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst($sipAccount->auth_type) }}</dd>
                </div>
                @if($sipAccount->allowed_ips)
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Allowed IPs</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $sipAccount->allowed_ips }}</dd>
                </div>
                @endif
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Caller ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">"{{ $sipAccount->caller_id_name }}" &lt;{{ $sipAccount->caller_id_number }}&gt;</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Max Channels</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $sipAccount->max_channels }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Codecs</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 sm:col-span-2 sm:mt-0">{{ $sipAccount->codec_allow }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 sm:col-span-2 sm:mt-0">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $sipAccount->status === 'active' ? 'bg-green-100 text-green-800' : ($sipAccount->status === 'suspended' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($sipAccount->status) }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Owner & Registration --}}
        <div class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Owner</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            @if($sipAccount->user_id === auth()->id())
                                <span class="text-gray-900">You ({{ $sipAccount->user->name }})</span>
                            @else
                                <a href="{{ route('reseller.clients.show', $sipAccount->user) }}" class="text-indigo-600 hover:text-indigo-900">{{ $sipAccount->user->name }}</a>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Role</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst($sipAccount->user->role) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $sipAccount->user->email }}</dd>
                    </div>
                </dl>
            </div>

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

                    @if($sipAccount->last_registered_at)
                        <div class="mt-4 text-sm text-gray-600">
                            <p>Last registered: {{ $sipAccount->last_registered_at->format('M d, Y H:i:s') }}</p>
                            <p>From IP: <span class="font-mono">{{ $sipAccount->last_registered_ip }}</span></p>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500">Never registered.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('reseller.sip-accounts.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">&larr; Back to SIP accounts</a>
    </div>
</x-reseller-layout>
