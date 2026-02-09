<x-admin-layout>
    <x-slot name="header">Edit Blacklist Entry</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Blacklist Entry</h2>
                <p class="page-subtitle">Prefix: <span class="font-mono">{{ $blacklist->prefix }}</span></p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.blacklist.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Blacklist
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.blacklist.update', $blacklist) }}" x-data="{ appliesTo: '{{ old('applies_to', $blacklist->applies_to) }}' }">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Prefix Details</h3>
                        <p class="form-card-subtitle">Calls to numbers matching this prefix will be blocked</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="prefix" class="form-label">Prefix</label>
                            <input type="text" id="prefix" name="prefix" value="{{ old('prefix', $blacklist->prefix) }}" required
                                   class="form-input font-mono">
                            <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" id="description" name="description" value="{{ old('description', $blacklist->description) }}"
                                   placeholder="e.g. Premium rate numbers" class="form-input">
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Scope</h3>
                        <p class="form-card-subtitle">Define who this blacklist entry applies to</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label class="form-label">Applies To</label>
                            <div class="balance-operation-grid mt-2">
                                <label class="balance-operation-option cursor-pointer" :class="appliesTo === 'all' ? 'blacklist-scope-global-active' : ''">
                                    <input type="radio" name="applies_to" value="all" x-model="appliesTo" class="sr-only">
                                    <div class="balance-operation-icon blacklist-scope-icon-global">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="balance-operation-text">
                                        <span class="balance-operation-title">All Users (Global)</span>
                                        <span class="balance-operation-desc">Block for everyone</span>
                                    </div>
                                    <div class="balance-operation-check" x-show="appliesTo === 'all'">
                                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </label>

                                <label class="balance-operation-option cursor-pointer" :class="appliesTo === 'specific_users' ? 'blacklist-scope-user-active' : ''">
                                    <input type="radio" name="applies_to" value="specific_users" x-model="appliesTo" class="sr-only">
                                    <div class="balance-operation-icon blacklist-scope-icon-user">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="balance-operation-text">
                                        <span class="balance-operation-title">Specific User</span>
                                        <span class="balance-operation-desc">Block for one user only</span>
                                    </div>
                                    <div class="balance-operation-check" x-show="appliesTo === 'specific_users'">
                                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('applies_to')" class="mt-2" />
                        </div>

                        <div x-show="appliesTo === 'specific_users'" x-transition class="form-group">
                            <label for="user_id" class="form-label">User</label>
                            <select id="user_id" name="user_id" class="form-input">
                                <option value="">Select a user...</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $blacklist->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.blacklist.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Entry
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Entry Info</h3>
                    </div>
                    <div class="detail-card-body space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Created</span>
                            <span class="text-gray-900">{{ $blacklist->created_at?->format('M d, Y') }}</span>
                        </div>
                        @if($blacklist->creator)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Created By</span>
                                <span class="text-gray-900">{{ $blacklist->creator->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Danger Zone</h3>
                    </div>
                    <div class="detail-card-body">
                        <form method="POST" action="{{ route('admin.blacklist.destroy', $blacklist) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full btn-danger" onclick="return confirm('Delete this blacklist entry?')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete Entry
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
