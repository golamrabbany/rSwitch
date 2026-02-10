<x-recharge-admin-layout>
    <x-slot name="header">SIP Accounts</x-slot>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">SIP Accounts</h3>
                <p class="text-sm text-gray-500">View-only access to SIP accounts under your assigned resellers</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <form action="{{ route('recharge-admin.sip-accounts.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username or caller ID..." class="form-input text-sm">
                </div>
                <select name="status" class="form-select text-sm w-40">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
                <button type="submit" class="btn-secondary text-sm">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('recharge-admin.sip-accounts.index') }}" class="btn-ghost text-sm">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Owner</th>
                        <th>Caller ID</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sipAccounts as $sip)
                        <tr>
                            <td class="font-medium text-gray-900">{{ $sip->username }}</td>
                            <td>
                                <a href="{{ route('recharge-admin.users.show', $sip->user_id) }}" class="text-amber-600 hover:underline">
                                    {{ $sip->user?->name ?? 'N/A' }}
                                </a>
                                <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $sip->user?->role === 'reseller' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($sip->user?->role ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="text-sm text-gray-500">{{ $sip->caller_id_number ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $sip->status === 'active' ? 'badge-success' : ($sip->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ ucfirst($sip->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('recharge-admin.sip-accounts.show', $sip) }}" class="btn-ghost text-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No SIP accounts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sipAccounts->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $sipAccounts->links() }}
            </div>
        @endif
    </div>
</x-recharge-admin-layout>
