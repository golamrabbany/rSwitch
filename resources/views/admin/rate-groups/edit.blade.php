<x-admin-layout>
    <x-slot name="header">Edit Rate Group</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Rate Group</h2>
                <p class="page-subtitle">{{ $rateGroup->name }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Details
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.rate-groups.update', $rateGroup) }}" x-data="{ type: '{{ old('type', $rateGroup->type) }}' }">
        @csrf
        @method('PUT')

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
                                <input type="text" id="name" name="name" value="{{ old('name', $rateGroup->name) }}" required class="form-input" placeholder="e.g., Default Rates, Premium Rates">
                                <p class="form-hint">A descriptive name for this rate group</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" rows="3" class="form-input" placeholder="Optional description for this rate group">{{ old('description', $rateGroup->description) }}</textarea>
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
                                        <option value="{{ $group->id }}" {{ old('parent_rate_group_id', $rateGroup->parent_rate_group_id) == $group->id ? 'selected' : '' }}>
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
                    <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Rate Group
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Current Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Rate Group Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $rateGroup->name }}</p>
                                <span class="badge {{ $rateGroup->type === 'admin' ? 'badge-blue' : 'badge-purple' }}">
                                    {{ ucfirst($rateGroup->type) }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $rateGroup->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Last Updated</span>
                                <span class="text-gray-900">{{ $rateGroup->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Total Rates</span>
                                <span class="text-gray-900 font-medium">{{ $rateGroup->rates()->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Assigned Users</span>
                                <span class="text-gray-900 font-medium">{{ $rateGroup->users()->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($rateGroup->parentRateGroup)
                {{-- Parent Rate Group --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Parent Rate Group</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $rateGroup->parentRateGroup->name }}</p>
                                <p class="text-xs text-gray-500">{{ $rateGroup->parentRateGroup->rates()->count() }} rates</p>
                            </div>
                            <span class="badge badge-blue">Admin</span>
                        </div>
                        <a href="{{ route('admin.rate-groups.show', $rateGroup->parentRateGroup) }}" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                            View Parent Group
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                @endif

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

                {{-- Quick Actions --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Actions</h3>
                    </div>
                    <div class="detail-card-body space-y-2">
                        <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="quick-action-btn">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View Rate Group Details
                        </a>
                        <a href="{{ route('admin.rate-groups.rates.create', $rateGroup) }}" class="quick-action-btn">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add New Rate
                        </a>
                        <a href="{{ route('admin.rate-groups.export', $rateGroup) }}" class="quick-action-btn">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export Rates (CSV)
                        </a>
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
                                <span>Changing type may affect assigned users</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Rate changes apply to future calls only</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Export rates before making changes</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
