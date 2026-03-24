<x-client-layout>
    <x-slot name="header">My Profile</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">My Profile</h2>
            <p class="page-subtitle">Manage your account information and password</p>
        </div>
    </div>

    {{-- Profile Hero --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-2xl">{{ substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-xl font-semibold text-gray-900">{{ auth()->user()->name }}</h3>
                <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
                <div class="flex items-center gap-4 mt-2">
                    <span class="badge badge-blue">{{ ucfirst(auth()->user()->role) }}</span>
                    <span class="text-xs text-gray-400">Member since {{ auth()->user()->created_at->format('M d, Y') }}</span>
                </div>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-xs text-gray-500 mb-1">Balance</p>
                <p class="text-2xl font-bold text-indigo-600">{{ format_currency(auth()->user()->balance) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Account Details --}}
        <div class="detail-card lg:col-span-1">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Account Details</h3>
            </div>
            <div class="p-5">
                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                        <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">Full Name</p>
                            <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                        <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">Email Address</p>
                            <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                        <div class="w-9 h-9 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">Account Type</p>
                            <p class="text-sm font-medium text-gray-900">{{ ucfirst(auth()->user()->billing_type) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                        <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">Member Since</p>
                            <p class="text-sm font-medium text-gray-900">{{ auth()->user()->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                        <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">KYC Status</p>
                            @if(auth()->user()->kyc_status === 'approved')
                                <span class="badge badge-success">Approved</span>
                            @elseif(auth()->user()->kyc_status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @else
                                <span class="badge badge-gray">{{ ucfirst(auth()->user()->kyc_status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="detail-card lg:col-span-2">
            <div class="detail-card-header">
                <div class="flex items-start justify-between w-full">
                    <div>
                        <h3 class="detail-card-title">Change Password</h3>
                        <p class="text-sm text-gray-500 mt-1">Use a strong password to keep your account secure</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
            </div>
            <div class="p-6">
                @if(session('password-updated'))
                    <div class="mb-5 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm text-emerald-700">Password updated successfully.</span>
                    </div>
                @endif
                <form method="POST" action="{{ route('client.profile.password') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" required class="form-input" autocomplete="current-password" placeholder="Enter your current password">
                        <p class="text-xs text-gray-400 mt-1">Required to verify your identity</p>
                        @error('current_password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" required class="form-input" autocomplete="new-password" placeholder="Enter new password">
                            <p class="text-xs text-gray-400 mt-1">Minimum 8 characters</p>
                            @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" required class="form-input" autocomplete="new-password" placeholder="Confirm new password">
                            <p class="text-xs text-gray-400 mt-1">Must match new password</p>
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-client-layout>
