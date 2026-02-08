<x-admin-layout>
    <x-slot name="header">Edit Ring Group</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Ring Group</h2>
                <p class="page-subtitle">Update: {{ $ringGroup->name }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.ring-groups.show', $ringGroup) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Details
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.ring-groups.update', $ringGroup) }}" x-data="ringGroupForm()">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Settings --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Basic Settings</h3>
                        <p class="form-card-subtitle">Ring group name and description</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $ringGroup->name) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', $ringGroup->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="disabled" {{ old('status', $ringGroup->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" rows="2" class="form-input" placeholder="Optional description">{{ old('description', $ringGroup->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="user_id" class="form-label">Owner (Optional)</label>
                                <select id="user_id" name="user_id" class="form-input">
                                    <option value="">Global (no owner)</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id', $ringGroup->user_id) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Assign to a reseller or client for scoping</p>
                                <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ring Strategy --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Ring Strategy</h3>
                        <p class="form-card-subtitle">How calls are distributed to members</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="strategy" class="form-label">Strategy</label>
                                <select id="strategy" name="strategy" required class="form-input">
                                    <option value="simultaneous" {{ old('strategy', $ringGroup->strategy) === 'simultaneous' ? 'selected' : '' }}>Simultaneous (ring all at once)</option>
                                    <option value="sequential" {{ old('strategy', $ringGroup->strategy) === 'sequential' ? 'selected' : '' }}>Sequential (ring in order)</option>
                                    <option value="random" {{ old('strategy', $ringGroup->strategy) === 'random' ? 'selected' : '' }}>Random</option>
                                </select>
                                <x-input-error :messages="$errors->get('strategy')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="ring_timeout" class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" id="ring_timeout" name="ring_timeout" value="{{ old('ring_timeout', $ringGroup->ring_timeout) }}" required min="5" max="300" class="form-input">
                                <p class="form-hint">Total time to ring before giving up (5-300)</p>
                                <x-input-error :messages="$errors->get('ring_timeout')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Members --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="form-card-title">Members</h3>
                                <p class="form-card-subtitle">SIP accounts that will receive calls</p>
                            </div>
                            <button type="button" @click="addMember()" class="btn-action-secondary">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Member
                            </button>
                        </div>
                    </div>
                    <div class="form-card-body space-y-3">
                        <template x-for="(member, index) in members" :key="index">
                            <div class="member-row">
                                <div class="member-row-main">
                                    <label class="form-label-sm">SIP Account</label>
                                    <select :name="`members[${index}][sip_account_id]`" x-model="member.sip_account_id" required class="form-input">
                                        <option value="">Select SIP Account...</option>
                                        @foreach ($sipAccounts as $sip)
                                            <option value="{{ $sip->id }}">{{ $sip->username }} — {{ $sip->user->name ?? 'Unknown' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="member-row-field">
                                    <label class="form-label-sm">Priority</label>
                                    <input type="number" :name="`members[${index}][priority]`" x-model="member.priority" min="1" max="100" required class="form-input">
                                </div>
                                <div class="member-row-field">
                                    <label class="form-label-sm">Delay (s)</label>
                                    <input type="number" :name="`members[${index}][delay]`" x-model="member.delay" min="0" max="120" required class="form-input">
                                </div>
                                <div class="member-row-action">
                                    <button type="button" @click="removeMember(index)" x-show="members.length > 1" class="btn-icon-danger">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <x-input-error :messages="$errors->get('members')" class="mt-2" />
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.ring-groups.show', $ringGroup) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Ring Group
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Current Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Current Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-purple-800">{{ $ringGroup->name }}</p>
                                <p class="text-xs text-purple-600">{{ $ringGroup->members->count() }} members</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Status</span>
                                @if($ringGroup->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Disabled</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Strategy</span>
                                @if($ringGroup->strategy === 'simultaneous')
                                    <span class="badge badge-info">Simultaneous</span>
                                @elseif($ringGroup->strategy === 'sequential')
                                    <span class="badge badge-purple">Sequential</span>
                                @else
                                    <span class="badge badge-warning">Random</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $ringGroup->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ring Strategies --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Ring Strategies</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">Simultaneous</span>
                            </div>
                            <p class="text-xs text-gray-500">All phones ring at once. First to answer gets the call.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">Sequential</span>
                            </div>
                            <p class="text-xs text-gray-500">Ring in priority order with delay between members.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-warning">Random</span>
                            </div>
                            <p class="text-xs text-gray-500">Randomly selects which phone rings.</p>
                        </div>
                    </div>
                </div>

                {{-- Member Settings --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Member Settings</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-start py-2 border-b border-gray-100">
                                <span class="text-gray-600 font-medium">Priority</span>
                                <span class="text-xs text-gray-500 text-right">Lower = rings first<br>(for sequential)</span>
                            </div>
                            <div class="flex justify-between items-start py-2">
                                <span class="text-gray-600 font-medium">Delay</span>
                                <span class="text-xs text-gray-500 text-right">Seconds to wait<br>before ringing</span>
                            </div>
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
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Changes take effect immediately</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Disabled members are skipped</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Remove unused members to keep clean</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function ringGroupForm() {
            return {
                members: @json($ringGroup->members->map(fn ($m) => [
                    'sip_account_id' => (string) $m->id,
                    'priority' => $m->pivot->priority,
                    'delay' => $m->pivot->delay,
                ])->values()),
                addMember() {
                    this.members.push({ sip_account_id: '', priority: this.members.length + 1, delay: 0 });
                },
                removeMember(index) {
                    this.members.splice(index, 1);
                }
            };
        }
    </script>
</x-admin-layout>
