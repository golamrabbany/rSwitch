<x-recharge-admin-layout>
    <x-slot name="header">Users</x-slot>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Assigned Users</h3>
                <p class="text-sm text-gray-500">View-only access to resellers and clients under your assignment</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <form action="{{ route('recharge-admin.users.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." class="form-input text-sm">
                </div>
                <select name="role" class="form-select text-sm w-40">
                    <option value="">All Roles</option>
                    <option value="reseller" {{ request('role') === 'reseller' ? 'selected' : '' }}>Resellers</option>
                    <option value="client" {{ request('role') === 'client' ? 'selected' : '' }}>Clients</option>
                </select>
                <select name="status" class="form-select text-sm w-40">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
                <button type="submit" class="btn-secondary text-sm">Filter</button>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('recharge-admin.users.index') }}" class="btn-ghost text-sm">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-gray-600">{{ substr($user->name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $user->role === 'reseller' ? 'badge-info' : 'badge-gray' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="font-medium {{ $user->balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($user->balance, 2) }}
                            </td>
                            <td>
                                <span class="badge {{ $user->status === 'active' ? 'badge-success' : ($user->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('recharge-admin.users.show', $user) }}" class="btn-ghost text-sm">View</a>
                                    <a href="{{ route('recharge-admin.balance.create', ['user_id' => $user->id]) }}" class="btn-primary text-sm">Recharge</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-recharge-admin-layout>
