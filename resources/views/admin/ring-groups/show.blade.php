<x-admin-layout>
    <x-slot name="header">Ring Group Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $ringGroup->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @if($ringGroup->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-danger">Disabled</span>
                    @endif
                    @if($ringGroup->strategy === 'simultaneous')
                        <span class="badge badge-info">Simultaneous</span>
                    @elseif($ringGroup->strategy === 'sequential')
                        <span class="badge badge-purple">Sequential</span>
                    @else
                        <span class="badge badge-warning">Random</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.ring-groups.edit', $ringGroup) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <form method="POST" action="{{ route('admin.ring-groups.destroy', $ringGroup) }}" class="inline" onsubmit="return confirm('Delete ring group &quot;{{ $ringGroup->name }}&quot;? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Ring Group Settings --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Ring Group Settings</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Name</span>
                            <span class="detail-value">{{ $ringGroup->name }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            @if($ringGroup->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ring Strategy</span>
                            @if($ringGroup->strategy === 'simultaneous')
                                <span class="badge badge-info">Simultaneous</span>
                            @elseif($ringGroup->strategy === 'sequential')
                                <span class="badge badge-purple">Sequential</span>
                            @else
                                <span class="badge badge-warning">Random</span>
                            @endif
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ring Timeout</span>
                            <span class="detail-value">{{ $ringGroup->ring_timeout }} seconds</span>
                        </div>
                        @if($ringGroup->description)
                            <div class="detail-item md:col-span-2">
                                <span class="detail-label">Description</span>
                                <span class="detail-value">{{ $ringGroup->description }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Members --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Members ({{ $ringGroup->members->count() }})</h3>
                </div>
                <div class="detail-card-body p-0">
                    @if($ringGroup->members->isNotEmpty())
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>SIP Account</th>
                                    <th>Owner</th>
                                    <th>Priority</th>
                                    <th>Delay</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ringGroup->members as $member)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.sip-accounts.show', $member) }}" class="text-indigo-600 hover:text-indigo-800 font-medium form-input-mono">
                                                {{ $member->username }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($member->user)
                                                <a href="{{ route('admin.users.show', $member->user) }}" class="text-indigo-600 hover:text-indigo-700">
                                                    {{ $member->user->name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="font-medium">{{ $member->pivot->priority }}</span>
                                        </td>
                                        <td>
                                            <span class="form-input-mono">{{ $member->pivot->delay }}s</span>
                                        </td>
                                        <td>
                                            @if($member->status === 'active')
                                                <span class="badge badge-success">Active</span>
                                            @elseif($member->status === 'suspended')
                                                <span class="badge badge-warning">Suspended</span>
                                            @else
                                                <span class="badge badge-danger">Disabled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-6 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-sm">No members in this ring group</p>
                            <a href="{{ route('admin.ring-groups.edit', $ringGroup) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Add members</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- DIDs Using This Ring Group --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">DIDs Using This Ring Group</h3>
                </div>
                <div class="detail-card-body">
                    @if($dids->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($dids as $did)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                            </svg>
                                        </div>
                                        <a href="{{ route('admin.dids.show', $did) }}" class="text-indigo-600 hover:text-indigo-800 font-medium form-input-mono">
                                            {{ $did->number }}
                                        </a>
                                    </div>
                                    @if($did->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-gray">{{ ucfirst($did->status) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                            <p class="text-sm text-gray-500">No DIDs use this ring group as a destination</p>
                            <a href="{{ route('admin.dids.create') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Create a DID</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Owner Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Owner</h3>
                </div>
                <div class="detail-card-body">
                    @if($ringGroup->user)
                        <div class="flex items-center gap-3 mb-4">
                            <div class="avatar avatar-indigo">
                                {{ strtoupper(substr($ringGroup->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <a href="{{ route('admin.users.show', $ringGroup->user) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ $ringGroup->user->name }}
                                </a>
                                <p class="text-xs text-gray-500">{{ ucfirst($ringGroup->user->role) }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Email</span>
                                <span class="text-gray-900">{{ $ringGroup->user->email }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Status</span>
                                @if($ringGroup->user->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warning">{{ ucfirst($ringGroup->user->status) }}</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Global Ring Group</p>
                                <p class="text-xs text-gray-500">Not assigned to any user</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ring Strategy Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Strategy Info</h3>
                </div>
                <div class="detail-card-body">
                    @if($ringGroup->strategy === 'simultaneous')
                        <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-blue-800">Simultaneous</p>
                                <p class="text-xs text-blue-600">All phones ring at once</p>
                            </div>
                        </div>
                    @elseif($ringGroup->strategy === 'sequential')
                        <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-purple-800">Sequential</p>
                                <p class="text-xs text-purple-600">Ring in priority order</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Random</p>
                                <p class="text-xs text-amber-600">Random member selection</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Timestamps</h3>
                </div>
                <div class="detail-card-body">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Created</span>
                            <span class="text-gray-900">{{ $ringGroup->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Updated</span>
                            <span class="text-gray-900">{{ $ringGroup->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('admin.ring-groups.edit', $ringGroup) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Ring Group
                    </a>
                    <a href="{{ route('admin.dids.create', ['ring_group_id' => $ringGroup->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                        </svg>
                        Create DID with this Group
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('admin.ring-groups.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Ring Groups
        </a>
    </div>
</x-admin-layout>
