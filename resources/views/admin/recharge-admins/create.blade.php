<x-admin-layout>
    <x-slot name="header">Add Recharge Admin</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Add Recharge Admin</h2>
                <p class="page-subtitle">Create a new admin with balance-only operations</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.recharge-admins.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.recharge-admins.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side (2 columns) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Account Details Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Details</h3>
                        <p class="form-card-subtitle">Basic account information</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Enter full name">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="Enter email address">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Security Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Security</h3>
                        <p class="form-card-subtitle">Set account password</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" required class="form-input" placeholder="Enter password">
                                <p class="form-hint">Minimum 8 characters</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input" placeholder="Confirm password">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reseller Assignment Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Reseller Assignment</h3>
                        <p class="form-card-subtitle">Select resellers this admin can manage</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label class="form-label">Assigned Resellers</label>
                            <div class="border border-gray-200 rounded-lg max-h-64 overflow-y-auto">
                                @forelse($resellers as $reseller)
                                    <label class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                        <input type="checkbox" name="reseller_ids[]" value="{{ $reseller->id }}"
                                               {{ in_array($reseller->id, old('reseller_ids', [])) ? 'checked' : '' }}
                                               class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-900">{{ $reseller->name }}</span>
                                            <span class="text-xs text-gray-500 block">{{ $reseller->email }}</span>
                                        </div>
                                    </label>
                                @empty
                                    <div class="p-4 text-center text-gray-500 text-sm">No resellers available</div>
                                @endforelse
                            </div>
                            <p class="form-hint">Admin can only perform balance operations for assigned resellers</p>
                            <x-input-error :messages="$errors->get('reseller_ids')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.recharge-admins.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Recharge Admin
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
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Recharge Admin Creation</p>
                                <p class="text-xs text-amber-600">Account requires reseller assignment</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Can login immediately</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Balance operations only</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All actions are audited</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Access Level Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Access Level</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-amber">Recharge Admin</span>
                            </div>
                            <p class="text-xs text-gray-500">Can perform balance recharge and adjustments for assigned resellers and their clients.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-gray">View Only</span>
                            </div>
                            <p class="text-xs text-gray-500">Can view users, SIP accounts, DIDs, CDR, and transactions within scope.</p>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Assign at least one reseller for the admin to be useful</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use strong, unique passwords</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>All balance operations are fully audited</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
