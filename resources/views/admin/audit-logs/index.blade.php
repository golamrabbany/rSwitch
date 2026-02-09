<x-admin-layout>
    <x-slot name="header">Audit Logs</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-500 to-slate-700 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Audit Logs</h2>
                <p class="page-subtitle">Track system activity and changes</p>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="filter-row flex-wrap">
            <select name="user_id" class="filter-select">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>

            <input type="text" name="action" value="{{ request('action') }}" placeholder="Action (e.g. user.created)" class="filter-input w-48">

            <select name="entity_type" class="filter-select">
                <option value="">All Types</option>
                @foreach ($entityTypes as $type)
                    <option value="{{ $type }}" {{ request('entity_type') === $type ? 'selected' : '' }}>{{ class_basename($type) }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-date">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-date">

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>

            @if(request()->hasAny(['user_id', 'action', 'entity_type', 'date_from', 'date_to']))
                <a href="{{ route('admin.audit-logs.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>IP Address</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>
                            <div class="txn-date">
                                <span class="txn-date-main">{{ $log->created_at->format('M d, Y') }}</span>
                                <span class="txn-date-time">{{ $log->created_at->format('H:i:s') }}</span>
                            </div>
                        </td>
                        <td>
                            @if($log->user)
                                <div class="user-cell">
                                    <div class="avatar avatar-indigo">
                                        {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $log->user_id) }}" class="user-name text-indigo-600 hover:text-indigo-700">
                                            {{ $log->user->name }}
                                        </a>
                                        <div class="user-email">{{ ucfirst($log->user->role) }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="badge badge-gray">System</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-gray">{{ $log->action }}</span>
                        </td>
                        <td class="text-gray-600">
                            {{ class_basename($log->auditable_type) }} <span class="font-mono text-xs">#{{ $log->auditable_id }}</span>
                        </td>
                        <td>
                            <span class="font-mono text-sm text-gray-500">{{ $log->ip_address }}</span>
                        </td>
                        <td class="text-center whitespace-nowrap">
                            <a href="{{ route('admin.audit-logs.show', $log) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="empty-text">No audit logs found</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-6">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
