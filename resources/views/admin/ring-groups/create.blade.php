<x-admin-layout>
    <x-slot name="header">Create Ring Group</x-slot>

    <div class="max-w-3xl" x-data="ringGroupForm()">
        <form method="POST" action="{{ route('admin.ring-groups.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <h3 class="text-base font-semibold text-gray-900">Ring Group Settings</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               placeholder="e.g. Sales Team, Support Desk">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                  placeholder="Optional description">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <label for="strategy" class="block text-sm font-medium text-gray-700">Ring Strategy</label>
                        <select id="strategy" name="strategy" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="simultaneous" {{ old('strategy') === 'simultaneous' ? 'selected' : '' }}>Simultaneous (ring all at once)</option>
                            <option value="sequential" {{ old('strategy') === 'sequential' ? 'selected' : '' }}>Sequential (ring in order)</option>
                            <option value="random" {{ old('strategy') === 'random' ? 'selected' : '' }}>Random</option>
                        </select>
                        <x-input-error :messages="$errors->get('strategy')" class="mt-2" />
                    </div>

                    <div>
                        <label for="ring_timeout" class="block text-sm font-medium text-gray-700">Ring Timeout (seconds)</label>
                        <input type="number" id="ring_timeout" name="ring_timeout" value="{{ old('ring_timeout', 30) }}" required
                               min="5" max="300"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Total time to ring before giving up.</p>
                        <x-input-error :messages="$errors->get('ring_timeout')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="user_id" class="block text-sm font-medium text-gray-700">Owner (optional)</label>
                        <select id="user_id" name="user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Global (no owner)</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Assign to a reseller or client for scoping.</p>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                    </div>
                </div>
            </div>

            {{-- Members --}}
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Members</h3>
                    <button type="button" @click="addMember()"
                            class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-inset ring-indigo-300 hover:bg-indigo-50">
                        + Add Member
                    </button>
                </div>

                <template x-for="(member, index) in members" :key="index">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">SIP Account</label>
                            <select :name="`members[${index}][sip_account_id]`" x-model="member.sip_account_id" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select...</option>
                                @foreach ($sipAccounts as $sip)
                                    <option value="{{ $sip->id }}">{{ $sip->username }} — {{ $sip->user->name ?? 'Unknown' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-24">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Priority</label>
                            <input type="number" :name="`members[${index}][priority]`" x-model="member.priority" min="1" max="100" required
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div class="w-24">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Delay (s)</label>
                            <input type="number" :name="`members[${index}][delay]`" x-model="member.delay" min="0" max="120" required
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div class="pt-5">
                            <button type="button" @click="removeMember(index)" x-show="members.length > 1"
                                    class="text-red-500 hover:text-red-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <x-input-error :messages="$errors->get('members')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('admin.ring-groups.index') }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Create Ring Group
                </button>
            </div>
        </form>
    </div>

    <script>
        function ringGroupForm() {
            return {
                members: [{ sip_account_id: '', priority: 1, delay: 0 }],
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
