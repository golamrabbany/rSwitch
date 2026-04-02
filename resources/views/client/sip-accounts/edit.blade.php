<x-client-layout>
    <x-slot name="header">Edit {{ $sipAccount->username }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit SIP Account</h2>
                <p class="page-subtitle font-mono">{{ $sipAccount->username }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.sip-accounts.show', $sipAccount) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form (2/3) --}}
        <div class="lg:col-span-2">
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-start justify-between w-full">
                        <div>
                            <h3 class="detail-card-title">Account Settings</h3>
                            <p class="text-sm text-gray-500 mt-1">Update your SIP account password and caller ID</p>
                        </div>
                        @switch($sipAccount->status)
                            @case('active') <span class="badge badge-success">Active</span> @break
                            @case('suspended') <span class="badge badge-warning">Suspended</span> @break
                            @default <span class="badge badge-danger">Disabled</span>
                        @endswitch
                    </div>
                </div>
                <div class="p-6">
                    @if(session('success'))
                        <div class="mb-5 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm text-emerald-700">{{ session('success') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('client.sip-accounts.update', $sipAccount) }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        {{-- Username (read-only) --}}
                        <div>
                            <label class="form-label">Username (SIP ID)</label>
                            <input type="text" disabled value="{{ $sipAccount->username }}" class="form-input bg-gray-50 text-gray-500 font-mono">
                            <p class="text-xs text-gray-400 mt-1">Username cannot be changed</p>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="form-label">New Password</label>
                            <input type="text" name="password" value="{{ old('password') }}" class="form-input font-mono" placeholder="Leave blank to keep current">
                            <p class="text-xs text-gray-400 mt-1">Minimum 6 characters. Leave blank to keep current password.</p>
                            @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Caller ID --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Caller ID Name</label>
                                <input type="text" name="caller_id_name" value="{{ old('caller_id_name', $sipAccount->caller_id_name) }}" required class="form-input">
                                <p class="text-xs text-gray-400 mt-1">Display name for outgoing calls</p>
                                @error('caller_id_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Caller ID Number</label>
                                <input type="text" disabled value="{{ $sipAccount->caller_id_number }}" class="form-input font-mono bg-gray-50 text-gray-500">
                                <p class="text-xs text-gray-400 mt-1">Managed by your reseller</p>
                            </div>
                        </div>

                        {{-- Call Forwarding --}}
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <x-call-forward-fields :sipAccount="$sipAccount" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('client.sip-accounts.show', $sipAccount) }}" class="text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</a>
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Current Config --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Current Config</h3>
                </div>
                <div class="detail-card-body">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Auth Type</span>
                            <span class="text-sm font-medium text-gray-900">{{ ucfirst($sipAccount->auth_type) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Max Channels</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $sipAccount->max_channels }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Codecs</span>
                            <span class="text-sm font-mono text-gray-900">{{ $sipAccount->codec_allow }}</span>
                        </div>
                        @if($sipAccount->allowed_ips)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Allowed IPs</span>
                            <span class="text-sm font-mono text-gray-900">{{ $sipAccount->allowed_ips }}</span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Created</span>
                            <span class="text-sm text-gray-900">{{ $sipAccount->created_at?->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">What can you edit?</p>
                        <p class="text-sm text-blue-600 mt-0.5">You can update your SIP password and Caller ID Name. Other settings like Caller ID Number, channels, codecs, and auth type are managed by your reseller.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
