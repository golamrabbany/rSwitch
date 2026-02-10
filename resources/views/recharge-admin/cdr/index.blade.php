<x-recharge-admin-layout>
    <x-slot name="header">CDR / Reports</x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <div class="card-body py-4">
                <p class="text-sm text-gray-500">Total Calls</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats->total_calls ?? 0) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-sm text-gray-500">Answered</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($stats->answered_calls ?? 0) }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-sm text-gray-500">Total Duration</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format(($stats->total_duration ?? 0) / 60, 1) }} min</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body py-4">
                <p class="text-sm text-gray-500">Total Cost</p>
                <p class="text-2xl font-bold text-amber-600">${{ number_format($stats->total_cost ?? 0, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Call Detail Records</h3>
                <p class="text-sm text-gray-500">View-only access to CDR under your assigned resellers</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <form action="{{ route('recharge-admin.cdr.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Caller</label>
                    <input type="text" name="caller" value="{{ request('caller') }}" placeholder="Caller prefix..." class="form-input text-sm w-32">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Callee</label>
                    <input type="text" name="callee" value="{{ request('callee') }}" placeholder="Callee prefix..." class="form-input text-sm w-32">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Disposition</label>
                    <select name="disposition" class="form-select text-sm">
                        <option value="">All</option>
                        <option value="ANSWERED" {{ request('disposition') === 'ANSWERED' ? 'selected' : '' }}>Answered</option>
                        <option value="NO ANSWER" {{ request('disposition') === 'NO ANSWER' ? 'selected' : '' }}>No Answer</option>
                        <option value="BUSY" {{ request('disposition') === 'BUSY' ? 'selected' : '' }}>Busy</option>
                        <option value="FAILED" {{ request('disposition') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">User</label>
                    <select name="user_id" class="form-select text-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ ucfirst($user->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-secondary text-sm">Filter</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Caller</th>
                        <th>Callee</th>
                        <th>User</th>
                        <th>Duration</th>
                        <th>Disposition</th>
                        <th>Cost</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($calls as $call)
                        <tr>
                            <td class="text-sm text-gray-500">{{ $call->call_start->format('M d, Y H:i:s') }}</td>
                            <td class="font-medium">{{ $call->caller }}</td>
                            <td class="font-medium">{{ $call->callee }}</td>
                            <td>
                                @if($call->user)
                                    <a href="{{ route('recharge-admin.users.show', $call->user) }}" class="text-amber-600 hover:underline">
                                        {{ $call->user->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-sm">{{ gmdate('i:s', $call->duration ?? 0) }}</td>
                            <td>
                                <span class="badge {{ $call->disposition === 'ANSWERED' ? 'badge-success' : ($call->disposition === 'NO ANSWER' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ $call->disposition }}
                                </span>
                            </td>
                            <td class="font-medium">${{ number_format($call->total_cost ?? 0, 4) }}</td>
                            <td class="text-right">
                                <a href="{{ route('recharge-admin.cdr.show', ['uuid' => $call->uuid, 'date' => $call->call_start->toDateString()]) }}" class="btn-ghost text-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">No call records found for the selected date range</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($calls->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $calls->links() }}
            </div>
        @endif
    </div>
</x-recharge-admin-layout>
