<x-admin-layout>
    <x-slot name="header">Regular Admin Details</x-slot>

    {{-- Page Header with User Info --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Left: User Info --}}
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl font-bold text-indigo-600">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $admin->name }}</h2>
                        <span class="badge badge-info">Regular Admin</span>
                        @if($admin->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($admin->status === 'suspended')
                            <span class="badge badge-warning">Suspended</span>
                        @else
                            <span class="badge badge-danger">Disabled</span>
                        @endif
                    </div>
                    <p class="text-gray-500 mt-1">{{ $admin->email }}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Created {{ $admin->created_at->format('M d, Y') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-3">
                @if(auth()->user()->isSuperAdmin())
                    <form action="{{ route('admin.impersonate.start', $admin) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn-secondary text-amber-600 border-amber-300 hover:bg-amber-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Login As
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.admins.edit', $admin) }}" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                @if($admin->id !== auth()->id())
                    <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this admin?')">
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
            <div class="stat-icon bg-emerald-100 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $stats['resellers'] ?? $admin->assignedResellers->count() }}</span>
                <span class="stat-label">Assigned Resellers</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-sky-100 text-sky-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $stats['clients'] ?? 0 }}</span>
                <span class="stat-label">Total Clients</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $admin->two_factor_secret ? 'Yes' : 'No' }}</span>
                <span class="stat-label">2FA Enabled</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100 text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : 'Never' }}</span>
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
                            <span class="detail-value">{{ $admin->name }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email Address</span>
                            <span class="detail-value">{{ $admin->email }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Role</span>
                            <span class="detail-value">
                                <span class="badge badge-info">Regular Admin</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                @if($admin->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($admin->status === 'suspended')
                                    <span class="badge badge-warning">Suspended</span>
                                @else
                                    <span class="badge badge-danger">Disabled</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">{{ $admin->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Updated</span>
                            <span class="detail-value">{{ $admin->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Assigned Resellers Card --}}
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">Assigned Resellers</h3>
                    <span class="badge badge-gray">{{ $admin->assignedResellers->count() }} assigned</span>
                </div>
                @if($admin->assignedResellers->isEmpty())
                    <div class="p-8 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <p class="text-gray-500">No resellers assigned</p>
                        <a href="{{ route('admin.admins.edit', $admin) }}" class="text-sm text-indigo-600 hover:text-indigo-800 mt-2 inline-block">Assign resellers</a>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($admin->assignedResellers as $reseller)
                            <div class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <span class="text-xs font-semibold text-emerald-600">{{ strtoupper(substr($reseller->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $reseller) }}" class="font-medium text-gray-900 hover:text-indigo-600 text-sm">{{ $reseller->name }}</a>
                                        <p class="text-xs text-gray-500">{{ $reseller->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">{{ $reseller->children_count ?? $reseller->children->count() }} clients</span>
                                    @if($reseller->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($reseller->status) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Access Level Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Access Level</h3>
                </div>
                <div class="detail-card-body">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-indigo-900">Scoped Admin Access</h4>
                                <p class="mt-1 text-sm text-indigo-700">This admin can manage assigned resellers and their clients. No access to system settings or unassigned accounts.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>User Management</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>SIP Accounts</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>DIDs (assigned)</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>CDR (scoped)</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>System Settings</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Trunk Management</span>
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
                    <a href="{{ route('admin.admins.edit', $admin) }}" class="quick-action-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Account
                    </a>
                    <a href="{{ route('admin.audit-logs.index', ['user_id' => $admin->id]) }}" class="quick-action-btn">
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
                        <span class="text-gray-900">{{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : 'Never' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Email Verified</span>
                        <span class="text-gray-900">
                            @if($admin->email_verified_at)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-warning">No</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">2FA Enabled</span>
                        <span class="text-gray-900">
                            @if($admin->two_factor_secret)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-gray">No</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Back Link --}}
            <a href="{{ route('admin.admins.index') }}" class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Regular Admins
            </a>
        </div>
    </div>
</x-admin-layout>
