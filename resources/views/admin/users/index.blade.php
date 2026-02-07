<x-admin-layout>
    <x-slot name="header">Users</x-slot>

    {{-- Header with filters --}}
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <select name="role" onchange="this.form.submit()" class="form-select w-auto">
                    <option value="">All Roles</option>
                    <option value="reseller" {{ request('role') === 'reseller' ? 'selected' : '' }}>Resellers</option>
                    <option value="client" {{ request('role') === 'client' ? 'selected' : '' }}>Clients</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admins</option>
                </select>
                <select name="status" onchange="this.form.submit()" class="form-select w-auto">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..."
                           class="form-input w-64 pl-9">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="btn-secondary">Search</button>
                @if(request()->hasAny(['role', 'status', 'search']))
                    <a href="{{ route('admin.users.index') }}" class="btn-ghost text-gray-500">Clear</a>
                @endif
            </form>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">
                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create User
            </a>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Parent</th>
                    <th>Balance</th>
                    <th>KYC</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-xs font-semibold text-white">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="badge bg-purple-100 text-purple-800">Admin</span>
                            @elseif($user->role === 'reseller')
                                <span class="badge bg-blue-100 text-blue-800">Reseller</span>
                            @else
                                <span class="badge bg-emerald-100 text-emerald-800">Client</span>
                            @endif
                        </td>
                        <td>
                            @if($user->status === 'active')
                                <span class="badge-success">Active</span>
                            @elseif($user->status === 'suspended')
                                <span class="badge-warning">Suspended</span>
                            @else
                                <span class="badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td class="text-gray-500">
                            {{ $user->parent?->name ?? '-' }}
                        </td>
                        <td class="font-medium">
                            ${{ number_format($user->balance, 2) }}
                        </td>
                        <td>
                            @if($user->kyc_status === 'approved')
                                <span class="badge-success">Approved</span>
                            @elseif($user->kyc_status === 'pending')
                                <span class="badge-warning">Pending</span>
                            @elseif($user->kyc_status === 'rejected')
                                <span class="badge-danger">Rejected</span>
                            @else
                                <span class="badge-gray">Not Submitted</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-primary-600 hover:text-primary-800 font-medium text-sm">View</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-primary-600 hover:text-primary-800 font-medium text-sm">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No users found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
