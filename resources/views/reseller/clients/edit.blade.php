<x-reseller-layout>
    <x-slot name="header">Edit Client</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Client</h2>
                <p class="page-subtitle">Update details for {{ $client->name }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.clients.show', $client) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('reseller.clients.update', $client) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Account Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Details</h3>
                        <p class="form-card-subtitle">Basic account information</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $client->name) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $client->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" id="password" name="password" class="form-input" placeholder="Leave blank to keep current">
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div class="form-group md:col-span-2" id="password_confirm_group" style="display:none">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing & Limits --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing & Limits</h3>
                        <p class="form-card-subtitle">Rate plan and usage limits</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="billing_type" class="form-label">Billing Type</label>
                                <select id="billing_type" name="billing_type" required class="form-input">
                                    <option value="prepaid" {{ old('billing_type', $client->billing_type) === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                    <option value="postpaid" {{ old('billing_type', $client->billing_type) === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="rate_group_id" class="form-label">Rate Group</label>
                                <select id="rate_group_id" name="rate_group_id" class="form-input">
                                    <option value="">Select Rate Group</option>
                                    @foreach ($rateGroups as $rateGroup)
                                        <option value="{{ $rateGroup->id }}" {{ old('rate_group_id', $client->rate_group_id) == $rateGroup->id ? 'selected' : '' }}>{{ $rateGroup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="credit_limit" class="form-label">Credit Limit ({{ currency_symbol() }})</label>
                                <input type="number" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $client->credit_limit) }}" step="0.01" min="0" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="max_channels" class="form-label">Max Channels</label>
                                <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $client->max_channels) }}" min="1" class="form-input">
                                <p class="form-hint">Maximum concurrent calls</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.clients.show', $client) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary-reseller">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>

            {{-- Sidebar (1/3) --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Account Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Status</span>
                                @if($client->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warning">Suspended</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">KYC</span>
                                @switch($client->kyc_status)
                                    @case('approved')
                                        <span class="badge badge-success">Approved</span>
                                        @break
                                    @case('pending')
                                        <span class="badge badge-warning">Pending</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                        @break
                                    @default
                                        <span class="badge badge-gray">Not Submitted</span>
                                @endswitch
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Balance</span>
                                <span class="text-sm font-semibold text-gray-900">{{ format_currency($client->balance) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">SIP Accounts</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $client->sipAccounts->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Created</span>
                                <span class="text-sm text-gray-500">{{ $client->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Billing Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <span class="badge badge-info">Prepaid</span>
                            <p class="text-xs text-gray-500 mt-1">Must have balance to make calls. Real-time deduction.</p>
                        </div>
                        <div>
                            <span class="badge badge-warning">Postpaid</span>
                            <p class="text-xs text-gray-500 mt-1">Can use up to credit limit. Billed at end of period.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('password').addEventListener('input', function() {
            document.getElementById('password_confirm_group').style.display = this.value ? '' : 'none';
        });
    </script>
</x-reseller-layout>
