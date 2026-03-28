<x-reseller-layout>
    <x-slot name="header">Add Client</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Add Client</h2>
                <p class="page-subtitle">Create a new client account with KYC verification</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.clients.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('reseller.clients.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Login Information --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Login Information</h3>
                        <p class="form-card-subtitle">Credentials and account setup</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Enter account name">
                                <p class="form-hint">Display name for this account</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username / Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="Enter email address">
                                <p class="form-hint">Used as login username and for notifications</p>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" required class="form-input" placeholder="Min 8 characters">
                                <p class="form-hint">Minimum 8 characters</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" required class="form-input" placeholder="Confirm password">
                                <p class="form-hint">Re-enter password to confirm</p>
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
                                    <option value="prepaid" {{ old('billing_type') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                    <option value="postpaid" {{ old('billing_type') === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                                </select>
                                <p class="form-hint">Prepaid deducts in real-time</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rate Group</label>
                                <select name="rate_group_id" class="form-input">
                                    <option value="">Select Rate Group</option>
                                    @foreach ($rateGroups as $rateGroup)
                                        <option value="{{ $rateGroup->id }}" {{ old('rate_group_id') == $rateGroup->id ? 'selected' : '' }}>{{ $rateGroup->name }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Defines call pricing</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Credit Limit</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="credit_limit" value="{{ old('credit_limit', '0') }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">For postpaid accounts only</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Channels</label>
                                <input type="number" name="max_channels" value="{{ old('max_channels', min(10, $channelInfo['available'])) }}" min="1" max="{{ $channelInfo['available'] }}" class="form-input" placeholder="{{ $channelInfo['available'] }}">
                                <p class="form-hint">Available: {{ $channelInfo['available'] }} of {{ $channelInfo['total'] }} channels ({{ $channelInfo['used'] }} used)</p>
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
                                <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="form-input" placeholder="contact@example.com">
                                <p class="form-hint">Separate from login email</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" required class="form-input" placeholder="e.g. +8801712345678">
                                <p class="form-hint">Primary contact number</p>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alternative Phone</label>
                                <input type="text" name="alt_phone" value="{{ old('alt_phone') }}" class="form-input" placeholder="Optional">
                                <p class="form-hint">Secondary number</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" value="{{ old('address') }}" class="form-input" placeholder="Street address">
                                <p class="form-hint">Street address, building, floor</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="user_city" value="{{ old('user_city') }}" class="form-input" placeholder="City">
                                <p class="form-hint">City or district</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" name="user_state" value="{{ old('user_state') }}" class="form-input" placeholder="State / Division">
                                <p class="form-hint">State, province or division</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Country</label>
                                <input type="text" name="user_country" value="{{ old('user_country') }}" class="form-input" placeholder="Country">
                                <p class="form-hint">Full country name</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" value="{{ old('zip_code') }}" class="form-input" placeholder="Zip / Postal">
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
                                <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-input" placeholder="e.g. ABC Telecom Ltd">
                                <p class="form-hint">Registered business or trading name</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Email</label>
                                <input type="email" name="company_email" value="{{ old('company_email') }}" class="form-input" placeholder="info@company.com">
                                <p class="form-hint">Official business email</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Website</label>
                                <input type="text" name="company_website" value="{{ old('company_website') }}" class="form-input" placeholder="e.g. https://example.com">
                                <p class="form-hint">Company website URL</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" class="form-input" placeholder="Internal notes...">
                                <p class="form-hint">Internal remarks, not visible to client</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KYC Information --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">KYC Information</h3>
                        <p class="form-card-subtitle">Identity verification — submitted for admin approval</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="account_type" class="form-label">Account Type</label>
                                <select id="account_type" name="account_type" required class="form-input">
                                    <option value="individual" {{ old('account_type') === 'individual' ? 'selected' : '' }}>Individual</option>
                                    <option value="company" {{ old('account_type') === 'company' ? 'selected' : '' }}>Company</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="kyc_full_name" class="form-label">Full Name / Company</label>
                                <input type="text" id="kyc_full_name" name="kyc_full_name" value="{{ old('kyc_full_name') }}" required class="form-input" placeholder="As per official documents">
                                <x-input-error :messages="$errors->get('kyc_full_name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required class="form-input" placeholder="+880 1XXXXXXXXX">
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person') }}" class="form-input" placeholder="For company accounts">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-100">
                            <div class="form-group md:col-span-2">
                                <label for="address_line1" class="form-label">Address</label>
                                <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1') }}" required class="form-input" placeholder="Street address">
                                <x-input-error :messages="$errors->get('address_line1')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="city" class="form-label">City</label>
                                <input type="text" id="city" name="city" value="{{ old('city') }}" required class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="state" class="form-label">State / Province</label>
                                <input type="text" id="state" name="state" value="{{ old('state') }}" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="country" class="form-label">Country Code</label>
                                <input type="text" id="country" name="country" value="{{ old('country', 'BD') }}" required maxlength="2" class="form-input" placeholder="BD">
                                <p class="form-hint">2-letter ISO code</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-100">
                            <div class="form-group">
                                <label for="id_type" class="form-label">ID Type</label>
                                <select id="id_type" name="id_type" required class="form-input">
                                    <option value="national_id" {{ old('id_type') === 'national_id' ? 'selected' : '' }}>National ID</option>
                                    <option value="passport" {{ old('id_type') === 'passport' ? 'selected' : '' }}>Passport</option>
                                    <option value="driving_license" {{ old('id_type') === 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                    <option value="business_license" {{ old('id_type') === 'business_license' ? 'selected' : '' }}>Business License</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" id="id_number" name="id_number" value="{{ old('id_number') }}" required class="form-input">
                                <x-input-error :messages="$errors->get('id_number')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="id_expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" id="id_expiry_date" name="id_expiry_date" value="{{ old('id_expiry_date') }}" class="form-input">
                                <p class="form-hint">Optional</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-100">
                            <div class="form-group">
                                <label class="form-label">ID Front</label>
                                <input type="file" name="id_front" accept="image/*,.pdf" class="form-input text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                <p class="form-hint">JPG, PNG or PDF — max 5MB</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ID Back</label>
                                <input type="file" name="id_back" accept="image/*,.pdf" class="form-input text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                <p class="form-hint">JPG, PNG or PDF — max 5MB</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.clients.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary-reseller">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Client
                    </button>
                </div>
            </div>

            {{-- Sidebar (1/3) --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-emerald-800">New Client</p>
                                <p class="text-xs text-emerald-600">Account with KYC verification</p>
                            </div>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Account created immediately
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                KYC submitted as pending
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Features locked until approved
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Admin reviews KYC
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

                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">KYC Documents</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                National ID / Passport / License
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Front and back of ID
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                JPG, PNG or PDF up to 5MB
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Documents reviewed by admin
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
