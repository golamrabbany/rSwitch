<x-admin-layout>
    <x-slot name="header">Ring Group: {{ $ringGroup->name }}</x-slot>

    <div class="space-y-6">
        <div class="flex items-center gap-x-3">
            <a href="{{ route('admin.ring-groups.edit', $ringGroup) }}"
               class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit
            </a>
            <form method="POST" action="{{ route('admin.ring-groups.destroy', $ringGroup) }}"
                  onsubmit="return confirm('Delete ring group &quot;{{ $ringGroup->name }}&quot;? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                    Delete
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Settings card --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Settings</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ringGroup->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if ($ringGroup->status === 'active')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Disabled</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Strategy</dt>
                        <dd class="mt-1 text-sm">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $ringGroup->strategy === 'simultaneous' ? 'bg-blue-100 text-blue-700' : ($ringGroup->strategy === 'sequential' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ ucfirst($ringGroup->strategy) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ring Timeout</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ringGroup->ring_timeout }} seconds</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Owner</dt>
                        <dd class="mt-1 text-sm">
                            @if ($ringGroup->user)
                                <a href="{{ route('admin.users.show', $ringGroup->user) }}" class="text-indigo-600 hover:text-indigo-500">
                                    {{ $ringGroup->user->name }}
                                </a>
                            @else
                                <span class="text-gray-400">Global</span>
                            @endif
                        </dd>
                    </div>
                    @if ($ringGroup->description)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ringGroup->description }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ringGroup->created_at->format('M j, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $ringGroup->updated_at->format('M j, Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="space-y-6">
                {{-- Members card --}}
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Members ({{ $ringGroup->members->count() }})</h3>
                    @if ($ringGroup->members->isNotEmpty())
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">SIP Account</th>
                                        <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                                        <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                        <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Delay</th>
                                        <th class="py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($ringGroup->members as $member)
                                        <tr>
                                            <td class="py-2 text-sm">
                                                <a href="{{ route('admin.sip-accounts.show', $member) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                                    {{ $member->username }}
                                                </a>
                                            </td>
                                            <td class="py-2 text-sm text-gray-500">{{ $member->user->name ?? '—' }}</td>
                                            <td class="py-2 text-sm text-gray-900">{{ $member->pivot->priority }}</td>
                                            <td class="py-2 text-sm text-gray-500">{{ $member->pivot->delay }}s</td>
                                            <td class="py-2 text-sm">
                                                @if ($member->status === 'active')
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Active</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">{{ ucfirst($member->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">No members in this ring group.</p>
                    @endif
                </div>

                {{-- DIDs using this ring group --}}
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">DIDs Using This Ring Group</h3>
                    @if ($dids->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach ($dids as $did)
                                <li class="flex items-center gap-2 text-sm">
                                    <a href="{{ route('admin.dids.show', $did) }}" class="text-indigo-600 hover:text-indigo-500 font-mono">
                                        {{ $did->number }}
                                    </a>
                                    @if ($did->status === 'active')
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Active</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 italic">No DIDs use this ring group as a destination.</p>
                    @endif
                </div>
            </div>
        </div>

        <a href="{{ route('admin.ring-groups.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            &larr; Back to Ring Groups
        </a>
    </div>
</x-admin-layout>
