<x-recharge-admin-layout>
    <x-slot name="header">DIDs</x-slot>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">DIDs</h3>
                <p class="text-sm text-gray-500">View-only access to DIDs under your assigned resellers</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <form action="{{ route('recharge-admin.dids.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search DID number..." class="form-input text-sm">
                </div>
                <select name="status" class="form-select text-sm w-40">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="btn-secondary text-sm">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('recharge-admin.dids.index') }}" class="btn-ghost text-sm">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>DID Number</th>
                        <th>Assigned To</th>
                        <th>Trunk</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dids as $did)
                        <tr>
                            <td class="font-medium text-gray-900">{{ $did->number }}</td>
                            <td>
                                @if($did->assignedUser)
                                    <a href="{{ route('recharge-admin.users.show', $did->assignedUser) }}" class="text-amber-600 hover:underline">
                                        {{ $did->assignedUser->name }}
                                    </a>
                                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $did->assignedUser->role === 'reseller' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($did->assignedUser->role) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-500">{{ $did->trunk?->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $did->status === 'active' ? 'badge-success' : 'badge-gray' }}">
                                    {{ ucfirst($did->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('recharge-admin.dids.show', $did) }}" class="btn-ghost text-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No DIDs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dids->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $dids->links() }}
            </div>
        @endif
    </div>
</x-recharge-admin-layout>
