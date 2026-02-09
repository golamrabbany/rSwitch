<x-admin-layout>
    <x-slot name="header">Add Blacklist Entry</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Add Blacklist Entry</h2>
                <p class="page-subtitle">Block calls to a destination prefix</p>
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

    <form method="POST" action="{{ route('admin.blacklist.store') }}" x-data="{ appliesTo: '{{ old('applies_to', 'all') }}' }">
        @csrf

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
                            <input type="text" id="prefix" name="prefix" value="{{ old('prefix') }}" required
                                   placeholder="e.g. +44900, 1900" class="form-input font-mono">
                            <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" id="description" name="description" value="{{ old('description') }}"
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
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Entry
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <span>Calls matching prefix will be blocked</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Global blocks apply to all users</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Whitelist takes priority over blacklist</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Example Prefixes</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono">+44900</code> UK premium</li>
                            <li><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono">1900</code> US premium</li>
                            <li><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono">+882</code> Satellite</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
