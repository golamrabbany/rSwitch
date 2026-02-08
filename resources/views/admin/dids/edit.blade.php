<x-admin-layout>
    <x-slot name="header">Edit DID</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit DID</h2>
                <p class="page-subtitle font-mono">{{ $did->number }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dids.show', $did) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Details
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.dids.update', $did) }}" x-data="{
        destinationType: '{{ old('destination_type', $did->destination_type) }}'
    }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- DID Information --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">DID Information</h3>
                        <p class="form-card-subtitle">Basic number details</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Number</label>
                                <p class="mt-1 text-sm font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded-md border border-gray-200">{{ $did->number }}</p>
                                <input type="hidden" name="number" value="{{ $did->number }}">
                                <p class="form-hint">Number cannot be changed after creation.</p>
                            </div>
                            <div class="form-group">
                                <label for="provider" class="form-label">Provider</label>
                                <input type="text" id="provider" name="provider" value="{{ old('provider', $did->provider) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('provider')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="trunk_id" class="form-label">Incoming Trunk</label>
                                <select id="trunk_id" name="trunk_id" required class="form-input">
                                    <option value="">Select a trunk...</option>
                                    @foreach ($trunks as $trunk)
                                        <option value="{{ $trunk->id }}" {{ old('trunk_id', $did->trunk_id) == $trunk->id ? 'selected' : '' }}>
                                            {{ $trunk->name }} ({{ $trunk->provider }}) — {{ $trunk->direction }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Only incoming/both trunks are shown.</p>
                                <x-input-error :messages="$errors->get('trunk_id')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', $did->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="unassigned" {{ old('status', $did->status) === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                                    <option value="disabled" {{ old('status', $did->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Assignment --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Assignment</h3>
                        <p class="form-card-subtitle">Assign DID to a user</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="assigned_to_user_id" class="form-label">Assigned User</label>
                            <select id="assigned_to_user_id" name="assigned_to_user_id" class="form-input">
                                <option value="">Unassigned</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to_user_id', $did->assigned_to_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('assigned_to_user_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Destination Routing --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Destination Routing</h3>
                        <p class="form-card-subtitle">Where incoming calls should go</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="form-group">
                                <label for="destination_type" class="form-label">Destination Type</label>
                                <select id="destination_type" name="destination_type" required x-model="destinationType" class="form-input">
                                    <option value="sip_account">SIP Account</option>
                                    <option value="external">External Number</option>
                                    <option value="ring_group">Ring Group</option>
                                </select>
                                <x-input-error :messages="$errors->get('destination_type')" class="mt-2" />
                            </div>

                            <div x-show="destinationType === 'sip_account'" x-cloak class="form-group">
                                <label for="destination_id" class="form-label">SIP Account</label>
                                <select id="destination_id" name="destination_id" class="form-input">
                                    <option value="">Select SIP account...</option>
                                    @foreach ($sipAccounts as $sip)
                                        <option value="{{ $sip->id }}" {{ old('destination_id', $did->destination_id) == $sip->id ? 'selected' : '' }}>
                                            {{ $sip->username }} — {{ $sip->user->name ?? 'Unknown' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Incoming calls will ring this SIP account.</p>
                                <x-input-error :messages="$errors->get('destination_id')" class="mt-2" />
                            </div>

                            <div x-show="destinationType === 'ring_group'" x-cloak class="form-group">
                                <label for="destination_id_rg" class="form-label">Ring Group</label>
                                <select id="destination_id_rg" name="destination_id" class="form-input">
                                    <option value="">Select ring group...</option>
                                    @foreach ($ringGroups as $rg)
                                        <option value="{{ $rg->id }}" {{ old('destination_id', $did->destination_type === 'ring_group' ? $did->destination_id : '') == $rg->id ? 'selected' : '' }}>
                                            {{ $rg->name }} — {{ ucfirst($rg->strategy) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Incoming calls will ring all group members.</p>
                                <x-input-error :messages="$errors->get('destination_id')" class="mt-2" />
                            </div>

                            <div x-show="destinationType === 'external'" x-cloak class="form-group">
                                <label for="destination_number" class="form-label">External Number</label>
                                <input type="text" id="destination_number" name="destination_number" value="{{ old('destination_number', $did->destination_number) }}"
                                       class="form-input font-mono" placeholder="+1234567890">
                                <p class="form-hint">Calls will be forwarded via an outgoing trunk.</p>
                                <x-input-error :messages="$errors->get('destination_number')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing</h3>
                        <p class="form-card-subtitle">Monthly cost and pricing</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="monthly_cost" class="form-label">Monthly Cost ($)</label>
                                <input type="number" id="monthly_cost" name="monthly_cost" value="{{ old('monthly_cost', $did->monthly_cost) }}" required
                                       step="0.0001" min="0" max="9999.9999" class="form-input">
                                <p class="form-hint">Your cost from the provider.</p>
                                <x-input-error :messages="$errors->get('monthly_cost')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="monthly_price" class="form-label">Monthly Price ($)</label>
                                <input type="number" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $did->monthly_price) }}" required
                                       step="0.0001" min="0" max="9999.9999" class="form-input">
                                <p class="form-hint">Price charged to the client.</p>
                                <x-input-error :messages="$errors->get('monthly_price')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.dids.show', $did) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- DID Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">DID Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-mono font-medium text-gray-900">{{ $did->number }}</p>
                                <span class="badge {{ $did->status === 'active' ? 'badge-success' : ($did->status === 'unassigned' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ ucfirst($did->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">DID ID</span>
                                <span class="font-mono text-gray-900">#{{ $did->id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $did->created_at->format('M d, Y') }}</span>
                            </div>
                            @if($did->assignedUser)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Owner</span>
                                <a href="{{ route('admin.users.show', $did->assignedUser) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $did->assignedUser->name }}
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Destination Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Destination Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">SIP</span>
                            </div>
                            <p class="text-xs text-gray-500">Route to a specific SIP account.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">Ring Group</span>
                            </div>
                            <p class="text-xs text-gray-500">Ring multiple extensions.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-warning">External</span>
                            </div>
                            <p class="text-xs text-gray-500">Forward to external number.</p>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Tips</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Changes take effect immediately</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Disable to stop routing calls</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Active calls may use old routing</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
