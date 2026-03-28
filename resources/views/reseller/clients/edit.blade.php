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

                {{-- Login Information --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Login Information</h3>
                        <p class="form-card-subtitle">Credentials and account status</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="name" value="{{ old('name', $client->name) }}" required class="form-input">
                                <p class="form-hint">Display name for this account</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username / Email</label>
                                <input type="email" name="email" value="{{ old('email', $client->email) }}" required class="form-input">
                                <p class="form-hint">Used as login username</p>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" required class="form-input">
                                <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $client->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                            <p class="form-hint">Suspended accounts cannot make calls</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" id="password" name="password" class="form-input" placeholder="Leave blank to keep current">
                                <p class="form-hint">Minimum 8 characters</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div class="form-group" id="password_confirm_group" style="display:none">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-input" placeholder="Confirm new password">
                                <p class="form-hint">Re-enter to confirm</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing Info --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing Info</h3>
                        <p class="form-card-subtitle">Rate plan, balance and usage limits</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Billing Type</label>
                                <select name="billing_type" required class="form-input">
                                    <option value="prepaid" {{ old('billing_type', $client->billing_type) === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                    <option value="postpaid" {{ old('billing_type', $client->billing_type) === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                                </select>
                                <p class="form-hint">Prepaid deducts in real-time</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rate Group</label>
                                <select name="rate_group_id" class="form-input">
                                    <option value="">Select Rate Group</option>
                                    @foreach ($rateGroups as $rateGroup)
                                        <option value="{{ $rateGroup->id }}" {{ old('rate_group_id', $client->rate_group_id) == $rateGroup->id ? 'selected' : '' }}>{{ $rateGroup->name }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Defines call pricing</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Credit Limit</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $client->credit_limit) }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">For postpaid accounts only</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Channels</label>
                                <input type="number" name="max_channels" value="{{ old('max_channels', $client->max_channels) }}" min="1" max="{{ $channelInfo['available'] + $client->max_channels }}" class="form-input">
                                <p class="form-hint">Available: {{ $channelInfo['available'] }} of {{ $channelInfo['total'] }} channels ({{ $channelInfo['used'] }} used by other clients)</p>
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Contact</h3>
                        <p class="form-card-subtitle">Phone numbers and address</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" value="{{ old('contact_email', $client->contact_email) }}" class="form-input" placeholder="contact@example.com">
                                <p class="form-hint">Separate from login email</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="form-input" placeholder="e.g. +8801712345678">
                                <p class="form-hint">Primary contact number</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alternative Phone</label>
                                <input type="text" name="alt_phone" value="{{ old('alt_phone', $client->alt_phone) }}" class="form-input" placeholder="Optional">
                                <p class="form-hint">Secondary number</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" value="{{ old('address', $client->address) }}" class="form-input" placeholder="Street address">
                                <p class="form-hint">Street address, building, floor</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" value="{{ old('city', $client->city) }}" class="form-input" placeholder="City">
                                <p class="form-hint">City or district</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" name="state" value="{{ old('state', $client->state) }}" class="form-input" placeholder="State / Division">
                                <p class="form-hint">State, province or division</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" value="{{ old('country', $client->country) }}" class="form-input" placeholder="Country">
                                <p class="form-hint">Full country name</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" value="{{ old('zip_code', $client->zip_code) }}" class="form-input" placeholder="Zip / Postal">
                                <p class="form-hint">Postal or zip code</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Company Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Company Details</h3>
                        <p class="form-card-subtitle">Business information (optional)</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" value="{{ old('company_name', $client->company_name) }}" class="form-input" placeholder="e.g. ABC Telecom Ltd">
                                <p class="form-hint">Registered business or trading name</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Email</label>
                                <input type="email" name="company_email" value="{{ old('company_email', $client->company_email) }}" class="form-input" placeholder="info@company.com">
                                <p class="form-hint">Official business email</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Website</label>
                                <input type="text" name="company_website" value="{{ old('company_website', $client->company_website) }}" class="form-input" placeholder="e.g. https://example.com">
                                <p class="form-hint">Company website URL</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" value="{{ old('notes', $client->notes) }}" class="form-input" placeholder="Internal notes...">
                                <p class="form-hint">Internal remarks, not visible to client</p>
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
