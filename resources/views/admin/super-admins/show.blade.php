<x-admin-layout>
    <x-slot name="header">Super Admin Details</x-slot>

    {{-- Page Header with User Info --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Left: User Info --}}
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl font-bold text-purple-600">{{ strtoupper(substr($superAdmin->name, 0, 1)) }}</span>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $superAdmin->name }}</h2>
                        <span class="badge badge-purple">Super Admin</span>
                        @if($superAdmin->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($superAdmin->status === 'suspended')
                            <span class="badge badge-warning">Suspended</span>
                        @else
                            <span class="badge badge-danger">Disabled</span>
                        @endif
                    </div>
                    <p class="text-gray-500 mt-1">{{ $superAdmin->email }}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Created {{ $superAdmin->created_at->format('M d, Y') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.super-admins.edit', $superAdmin) }}" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                @if($superAdmin->id !== auth()->id())
                    <form action="{{ route('admin.super-admins.destroy', $superAdmin) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this super admin?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">Full</span>
                <span class="stat-label">Access Level</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $superAdmin->two_factor_secret ? 'Yes' : 'No' }}</span>
                <span class="stat-label">2FA Enabled</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ ucfirst($superAdmin->status) }}</span>
                <span class="stat-label">Status</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100 text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $superAdmin->last_login_at ? $superAdmin->last_login_at->diffForHumans() : 'Never' }}</span>
                <span class="stat-label">Last Login</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Account Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Information</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Full Name</span>
                            <span class="detail-value">{{ $superAdmin->name }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email Address</span>
                            <span class="detail-value">{{ $superAdmin->email }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Role</span>
                            <span class="detail-value">
                                <span class="badge badge-purple">Super Admin</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                @if($superAdmin->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($superAdmin->status === 'suspended')
                                    <span class="badge badge-warning">Suspended</span>
                                @else
                                    <span class="badge badge-danger">Disabled</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">{{ $superAdmin->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Updated</span>
                            <span class="detail-value">{{ $superAdmin->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Access Level Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Access Level</h3>
                </div>
                <div class="detail-card-body">
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-purple-900">Full System Access</h4>
                                <p class="mt-1 text-sm text-purple-700">This super admin has unrestricted access to all system features including admin management, trunks, rates, and system settings.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Admin Management</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>User Management</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Trunk Management</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Rate Management</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Billing & Invoices</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>System Settings</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('admin.super-admins.edit', $superAdmin) }}" class="quick-action-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Account
                    </a>
                    <a href="{{ route('admin.audit-logs.index', ['user_id' => $superAdmin->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        View Audit Logs
                    </a>
                </div>
            </div>

            {{-- Account Activity --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Activity</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Last Login</span>
                        <span class="text-gray-900">{{ $superAdmin->last_login_at ? $superAdmin->last_login_at->diffForHumans() : 'Never' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Email Verified</span>
                        <span class="text-gray-900">
                            @if($superAdmin->email_verified_at)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-warning">No</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">2FA Enabled</span>
                        <span class="text-gray-900">
                            @if($superAdmin->two_factor_secret)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-gray">No</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Back Link --}}
            <a href="{{ route('admin.super-admins.index') }}" class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Super Admins
            </a>
        </div>
    </div>
</x-admin-layout>
