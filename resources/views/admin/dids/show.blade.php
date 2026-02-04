<x-admin-layout>
    <x-slot name="header">DID: {{ $did->number }}</x-slot>

    <div class="space-y-6">
        {{-- Action buttons --}}
        <div class="flex items-center gap-x-3">
            <a href="{{ route('admin.dids.edit', $did) }}"
               class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit
            </a>
            <form method="POST" action="{{ route('admin.dids.destroy', $did) }}"
                  onsubmit="return confirm('Delete DID {{ $did->number }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                    Delete
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Left column: DID Details --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">DID Details</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $did->number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Provider</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $did->provider }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Trunk</dt>
                        <dd class="mt-1 text-sm">
                            <a href="{{ route('admin.trunks.show', $did->trunk) }}" class="text-indigo-600 hover:text-indigo-500">
                                {{ $did->trunk->name }}
                            </a>
                            <span class="text-gray-500">({{ $did->trunk->direction }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if ($did->status === 'active')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
                            @elseif ($did->status === 'unassigned')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Unassigned</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Disabled</span>
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Destination</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($did->destination_type === 'sip_account' && $destinationSip)
                                <span class="inline-flex items-center gap-1">
                                    <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">SIP</span>
                                    <a href="{{ route('admin.sip-accounts.show', $destinationSip) }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $destinationSip->username }}
                                    </a>
                                    <span class="text-gray-500">— {{ $destinationSip->user->name ?? 'Unknown' }}</span>
                                </span>
                            @elseif ($did->destination_type === 'external' && $did->destination_number)
                                <span class="inline-flex items-center gap-1">
                                    <span class="text-xs font-medium text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded">EXT</span>
                                    <span class="font-mono">{{ $did->destination_number }}</span>
                                </span>
                            @else
                                <span class="text-gray-400">Not configured</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $did->created_at->format('M j, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $did->updated_at->format('M j, Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                {{-- Assignment card --}}
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Assignment</h3>
                    @if ($did->assignedUser)
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">User</dt>
                                <dd class="mt-1 text-sm">
                                    <a href="{{ route('admin.users.show', $did->assignedUser) }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $did->assignedUser->name }}
                                    </a>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Role</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($did->assignedUser->role) }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $did->assignedUser->email }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 italic">This DID is not assigned to any user.</p>
                    @endif
                </div>

                {{-- Billing card --}}
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Billing</h3>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Monthly Cost</dt>
                            <dd class="mt-1 text-sm text-gray-900">${{ number_format($did->monthly_cost, 4) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Monthly Price</dt>
                            <dd class="mt-1 text-sm text-gray-900">${{ number_format($did->monthly_price, 4) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Margin</dt>
                            <dd class="mt-1 text-sm">
                                @php
                                    $margin = $did->monthly_price - $did->monthly_cost;
                                    $marginPct = $did->monthly_price > 0 ? ($margin / $did->monthly_price) * 100 : 0;
                                @endphp
                                <span class="{{ $margin >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($margin, 4) }}
                                    ({{ number_format($marginPct, 1) }}%)
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.dids.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            &larr; Back to DIDs
        </a>
    </div>
</x-admin-layout>
