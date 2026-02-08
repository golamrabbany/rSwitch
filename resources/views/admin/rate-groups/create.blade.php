<x-admin-layout>
    <x-slot name="header">Create Rate Group</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Create Rate Group</h2>
                <p class="page-subtitle">Add a new rate group for pricing</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.rate-groups.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.rate-groups.store') }}" x-data="{ type: '{{ old('type', 'admin') }}' }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Rate Group Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Rate Group Details</h3>
                        <p class="form-card-subtitle">Basic information about the rate group</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group md:col-span-2">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g., Default Rates, Premium Rates">
                                <p class="form-hint">A descriptive name for this rate group</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" rows="3" class="form-input" placeholder="Optional description for this rate group">{{ old('description') }}</textarea>
                                <p class="form-hint">Optional notes about this rate group's purpose</p>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rate Group Type --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Rate Group Type</h3>
                        <p class="form-card-subtitle">Configure the rate group hierarchy</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="type" class="form-label">Type</label>
                            <select id="type" name="type" x-model="type" class="form-input">
                                <option value="admin">Admin</option>
                                <option value="reseller">Reseller</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        {{-- Type Info --}}
                        <div class="mt-4 p-3 rounded-lg" :class="{
                            'bg-blue-50 border border-blue-200': type === 'admin',
                            'bg-purple-50 border border-purple-200': type === 'reseller'
                        }">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" :class="{
                                    'text-blue-500': type === 'admin',
                                    'text-purple-500': type === 'reseller'
                                }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium" :class="{
                                        'text-blue-800': type === 'admin',
                                        'text-purple-800': type === 'reseller'
                                    }" x-text="type === 'admin' ? 'Admin Rate Group' : 'Reseller Rate Group'"></p>
                                    <p class="text-xs mt-1" :class="{
                                        'text-blue-600': type === 'admin',
                                        'text-purple-600': type === 'reseller'
                                    }" x-text="type === 'admin' ? 'System-wide rates used for cost calculation. These are your carrier rates.' : 'Reseller markup rates that inherit from an admin group. Used for reseller billing.'"></p>
                                </div>
                            </div>
                        </div>

                        <template x-if="type === 'reseller'">
                            <div class="form-group mt-4">
                                <label for="parent_rate_group_id" class="form-label">Parent Rate Group (Admin)</label>
                                <select id="parent_rate_group_id" name="parent_rate_group_id" class="form-input">
                                    <option value="">Select parent group...</option>
                                    @foreach ($adminGroups as $group)
                                        <option value="{{ $group->id }}" {{ old('parent_rate_group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Reseller rate groups inherit cost basis from the parent admin group</p>
                                <x-input-error :messages="$errors->get('parent_rate_group_id')" class="mt-2" />
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.rate-groups.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Rate Group
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Quick Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800">Rate Groups</p>
                                <p class="text-xs text-indigo-600">Organize rates for billing</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Group rates by destination prefix</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Assign to users for billing</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Import/export via CSV</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rate Group Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Rate Group Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-blue">Admin</span>
                            </div>
                            <p class="text-xs text-gray-500">System-wide cost rates from your carriers. Used as the base for profit margin calculations.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">Reseller</span>
                            </div>
                            <p class="text-xs text-gray-500">Markup rates for resellers. Inherits from an admin group to calculate margins.</p>
                        </div>
                    </div>
                </div>

                {{-- How It Works --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">How It Works</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3">
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">1</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Create Rate Group</p>
                                    <p class="text-xs text-gray-500">Define the group name and type</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">2</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Add Rates</p>
                                    <p class="text-xs text-gray-500">Add individual rates or import CSV</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">3</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Assign to Users</p>
                                    <p class="text-xs text-gray-500">Link users to this rate group</p>
                                </div>
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
                                <span>Create admin groups first for cost tracking</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use descriptive names for easy identification</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Import rates via CSV for bulk operations</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
