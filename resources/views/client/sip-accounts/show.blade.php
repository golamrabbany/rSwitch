<x-client-layout>
    <x-slot name="header">{{ $sipAccount->username }}</x-slot>

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
                    @switch($sipAccount->status)
                        @case('active') <span class="badge badge-success">Active</span> @break
                        @case('suspended') <span class="badge badge-warning">Suspended</span> @break
                        @default <span class="badge badge-danger">Disabled</span>
                    @endswitch
                    @if($provisioned)
                        <span class="badge badge-info">Provisioned</span>
                    @else
                        <span class="badge badge-gray">Not Provisioned</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.sip-accounts.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
            <a href="{{ route('client.sip-accounts.edit', $sipAccount) }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ $sipAccount->max_channels }}</p>
                <p class="stat-label">Max Channels</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-base">{{ ucfirst($sipAccount->auth_type) }}</p>
                <p class="stat-label">Auth Type</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-base">{{ $sipAccount->caller_id_name ?: '—' }}</p>
                <p class="stat-label">Caller ID</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-gray-100">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-base">{{ $sipAccount->created_at?->format('M d, Y') }}</p>
                <p class="stat-label">Created</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- SIP Configuration --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">SIP Configuration</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-y-5 gap-x-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Username</p>
                            <p class="text-sm font-mono font-semibold text-gray-900 mt-1">{{ $sipAccount->username }}</p>
                        </div>
                        <div x-data="{ show: false }">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Password</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-sm font-mono text-gray-900" x-text="show ? '{{ $sipAccount->password }}' : '••••••••••'"></span>
                                <button @click="show = !show" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Codecs</p>
                            <div class="flex gap-1.5 mt-1">
                                @foreach(explode(',', $sipAccount->codec_allow) as $codec)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">{{ trim($codec) }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Caller ID Name</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $sipAccount->caller_id_name ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Caller ID Number</p>
                            <p class="text-sm font-mono text-gray-900 mt-1">{{ $sipAccount->caller_id_number ?: '—' }}</p>
                        </div>
                        @if($sipAccount->allowed_ips)
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Allowed IPs</p>
                            <p class="text-sm font-mono text-gray-900 mt-1">{{ $sipAccount->allowed_ips }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Registration Status --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Registration</h3>
                </div>
                <div class="detail-card-body">
                    <div class="reg-status" data-username="{{ $sipAccount->username }}">
                        <span class="text-gray-300">Checking...</span>
                    </div>
                    @if($sipAccount->last_registered_at)
                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Last Registered</p>
                                <p class="text-sm text-gray-900 mt-1">{{ $sipAccount->last_registered_at->format('M d, Y h:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">From IP</p>
                                <p class="text-sm font-mono text-gray-900 mt-1">{{ $sipAccount->last_registered_ip }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('client.sip-accounts.edit', $sipAccount) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Edit Account</span>
                    </a>
                    <a href="{{ route('client.sip-accounts.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">All SIP Accounts</span>
                    </a>
                    <a href="{{ route('client.cdr.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Call Records</span>
                    </a>
                </div>
            </div>

            {{-- Account Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Info</h3>
                </div>
                <div class="detail-card-body">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Balance</span>
                            <span class="text-sm font-semibold text-gray-900">{{ format_currency(auth()->user()->balance) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Billing</span>
                            @if(auth()->user()->billing_type === 'prepaid')
                                <span class="badge badge-blue">Prepaid</span>
                            @else
                                <span class="badge badge-purple">Postpaid</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">KYC</span>
                            @if(auth()->user()->kyc_status === 'approved')
                                <span class="badge badge-success">Approved</span>
                            @else
                                <span class="badge badge-warning">{{ ucfirst(auth()->user()->kyc_status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var cell = document.querySelector('.reg-status');
        if (!cell) return;
        var username = cell.dataset.username;

        fetch('{{ route("client.sip-accounts.registration-status") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ usernames: [username] })
        })
        .then(function(r) { return r.json(); })
        .then(function(c) {
            if (c[username] && c[username].registered) {
                cell.innerHTML = '<span class="inline-flex items-center gap-2 text-emerald-600"><span class="relative flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span></span><span class="font-medium">Registered</span></span>';
            } else {
                cell.innerHTML = '<span class="inline-flex items-center gap-2 text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>Unregistered</span>';
            }
        })
        .catch(function() {
            cell.innerHTML = '<span class="inline-flex items-center gap-2 text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>Unregistered</span>';
        });
    });
    </script>
    @endpush
</x-client-layout>
