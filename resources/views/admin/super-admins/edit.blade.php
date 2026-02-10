<x-admin-layout>
    <x-slot name="header">Edit Super Admin</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Super Admin</h2>
                <p class="page-subtitle">Update account details for {{ $superAdmin->name }}</p>
            </div>
        </div>
        <div class="page-actions flex items-center gap-3">
            <span class="badge {{ $superAdmin->status === 'active' ? 'badge-success' : ($superAdmin->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                {{ ucfirst($superAdmin->status) }}
            </span>
            <a href="{{ route('admin.super-admins.show', $superAdmin) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Details
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.super-admins.update', $superAdmin) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side (2 columns) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Account Details Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Details</h3>
                        <p class="form-card-subtitle">Basic account information</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $superAdmin->name) }}" required class="form-input" placeholder="Enter full name">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" value="{{ old('email', $superAdmin->email) }}" required class="form-input" placeholder="Enter email address">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', $superAdmin->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $superAdmin->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="disabled" {{ old('status', $superAdmin->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Security Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Security</h3>
                        <p class="form-card-subtitle">Update account password</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" id="password" name="password" class="form-input" placeholder="Enter new password">
                                <p class="form-hint">Leave blank to keep current password</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Confirm new password">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Access Level Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Access Level</h3>
                        <p class="form-card-subtitle">Super Admin permissions</p>
                    </div>
                    <div class="form-card-body">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-purple-900">Full System Access</h4>
                                    <p class="text-sm text-purple-700 mt-1">Super admins have unrestricted access to all system features.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.super-admins.show', $superAdmin) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Current Account Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Account Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                                <span class="text-lg font-bold text-white">{{ strtoupper(substr($superAdmin->name, 0, 2)) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $superAdmin->name }}</p>
                                <span class="badge badge-purple">Super Admin</span>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Account ID</span>
                                <span class="font-mono text-gray-900">#{{ $superAdmin->id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">2FA</span>
                                <span class="badge {{ $superAdmin->two_factor_secret ? 'badge-success' : 'badge-gray' }}">
                                    {{ $superAdmin->two_factor_secret ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $superAdmin->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Links</h3>
                    </div>
                    <div class="detail-card-body space-y-2">
                        <a href="{{ route('admin.super-admins.show', $superAdmin) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span>View Profile</span>
                        </a>
                        <a href="{{ route('admin.audit-logs.index', ['user_id' => $superAdmin->id]) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <span>Audit Logs</span>
                        </a>
                    </div>
                </div>

                {{-- Tips Card --}}
                <div class="detail-card bg-amber-50 border-amber-200">
                    <div class="detail-card-body">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-amber-800">Important Notes</h4>
                                <ul class="mt-2 text-xs text-amber-700 space-y-1">
                                    <li>Changing status to "Disabled" will block login</li>
                                    <li>Super admins have full system access</li>
                                    <li>Use strong, unique passwords</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
