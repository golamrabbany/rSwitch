<x-admin-layout>
    <x-slot name="header">SIP Account Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title font-mono">{{ $sipAccount->username }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @if($sipAccount->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @elseif($sipAccount->status === 'suspended')
                        <span class="badge badge-warning">Suspended</span>
                    @else
                        <span class="badge badge-danger">Disabled</span>
                    @endif
                    @if($provisioned)
                        <span class="badge badge-info">Provisioned</span>
                    @else
                        <span class="badge badge-danger">Not Provisioned</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="page-actions">
            <form method="POST" action="{{ route('admin.sip-accounts.reprovision', $sipAccount) }}" class="inline">
                @csrf
                <button type="submit" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Re-provision
                </button>
            </form>
            <a href="{{ route('admin.sip-accounts.edit', $sipAccount) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <form method="POST" action="{{ route('admin.sip-accounts.destroy', $sipAccount) }}" class="inline" onsubmit="return confirm('Delete this SIP account? This will also remove it from Asterisk.')">
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
        {{-- SIP Configuration --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">SIP Configuration</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Username</span>
                            <span class="detail-value font-mono">{{ $sipAccount->username }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Auth Type</span>
                            <span class="detail-value">{{ ucfirst($sipAccount->auth_type) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Password</span>
                            <div x-data="{ show: false }" class="flex items-center gap-2">
                                <span x-show="!show" class="detail-value text-gray-400">••••••••••••</span>
                                <span x-show="show" x-cloak class="detail-value font-mono">{{ $sipAccount->password }}</span>
                                <button @click="show = !show" class="text-xs text-indigo-600 hover:text-indigo-800" x-text="show ? 'Hide' : 'Show'"></button>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Max Channels</span>
                            <span class="detail-value">{{ $sipAccount->max_channels }}</span>
                        </div>
                        @if($sipAccount->allowed_ips)
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">Allowed IPs</span>
                            <span class="detail-value font-mono">{{ $sipAccount->allowed_ips }}</span>
                        </div>
                        @endif
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">Codecs</span>
                            <span class="detail-value font-mono">{{ $sipAccount->codec_allow }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Caller ID --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Caller ID</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Name</span>
                            <span class="detail-value">{{ $sipAccount->caller_id_name }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Number</span>
                            <span class="detail-value font-mono">{{ $sipAccount->caller_id_number }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Registration Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Registration Status</h3>
                </div>
                <div class="detail-card-body">
                    @if($sipAccount->last_registered_at)
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Last Registered</span>
                                <span class="detail-value">{{ $sipAccount->last_registered_at->format('M d, Y H:i:s') }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">From IP</span>
                                <span class="detail-value font-mono">{{ $sipAccount->last_registered_ip }}</span>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">This SIP account has never registered.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Owner Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Owner</h3>
                </div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar avatar-indigo">
                            {{ strtoupper(substr($sipAccount->user->name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('admin.users.show', $sipAccount->user) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                {{ $sipAccount->user->name }}
                            </a>
                            <p class="text-xs text-gray-500">{{ ucfirst($sipAccount->user->role) }}</p>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Email</span>
                            <span class="text-gray-900">{{ $sipAccount->user->email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Balance</span>
                            <span class="text-gray-900 font-medium">${{ number_format($sipAccount->user->balance, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            @if($sipAccount->user->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-warning">{{ ucfirst($sipAccount->user->status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Provisioning Status --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Asterisk Status</h3>
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
                                <p class="text-xs text-emerald-600">Active in Asterisk realtime</p>
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
                                <p class="text-xs text-red-600">Not in Asterisk realtime</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('admin.cdr.index', ['search' => $sipAccount->username]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        View Call Records
                    </a>
                    <a href="{{ route('admin.users.show', $sipAccount->user) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        View Owner Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('admin.sip-accounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to SIP Accounts
        </a>
    </div>
</x-admin-layout>
