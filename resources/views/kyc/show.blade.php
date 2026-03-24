@php
    $isReseller = auth()->user()->isReseller();
    $layoutComponent = $isReseller ? 'reseller-layout' : 'client-layout';
@endphp

<x-dynamic-component :component="$layoutComponent">
    <x-slot name="header">KYC Verification</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">KYC Verification</h2>
            <p class="page-subtitle">Submit your identity information for account verification</p>
        </div>
    </div>

    {{-- Status Banner --}}
    @if($user->kyc_status === 'approved')
        <div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-emerald-800">KYC Approved</p>
                <p class="text-xs text-emerald-600">Your identity verification has been approved. No action needed.</p>
            </div>
        </div>
    @elseif($user->kyc_status === 'rejected')
        <div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-red-800">KYC Rejected</p>
                @if($user->kyc_rejected_reason)
                    <p class="text-xs text-red-600">Reason: {{ $user->kyc_rejected_reason }}</p>
                @endif
                <p class="text-xs text-red-600">Please update your information and resubmit.</p>
            </div>
        </div>
    @elseif($user->kyc_status === 'pending')
        <div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-amber-800">Under Review</p>
                <p class="text-xs text-amber-600">Your KYC verification is being reviewed. We'll notify you once it's processed.</p>
            </div>
        </div>
    @else
        <div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-blue-50 border border-blue-200">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-blue-800">KYC Required</p>
                <p class="text-xs text-blue-600">Please complete your KYC verification to access all features.</p>
            </div>
        </div>
    @endif

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-6 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm text-emerald-700">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- KYC Form (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Personal Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-start justify-between w-full">
                        <div>
                            <h3 class="detail-card-title">Personal Information</h3>
                            <p class="text-sm text-gray-500 mt-1">Identity details for verification</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('kyc.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label class="form-label">Account Type</label>
                            <select name="account_type" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <option value="individual" {{ old('account_type', $profile?->account_type) === 'individual' ? 'selected' : '' }}>Individual</option>
                                <option value="company" {{ old('account_type', $profile?->account_type) === 'company' ? 'selected' : '' }}>Company</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Select whether this is a personal or business account</p>
                            @error('account_type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Full Name / Company Name</label>
                                <input type="text" name="full_name" value="{{ old('full_name', $profile?->full_name) }}" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">Legal name as it appears on your ID</p>
                                @error('full_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" value="{{ old('contact_person', $profile?->contact_person) }}" class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">Required for company accounts only</p>
                                @error('contact_person') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $profile?->phone) }}" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">Primary contact number with country code</p>
                                @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Alternate Phone</label>
                                <input type="text" name="alt_phone" value="{{ old('alt_phone', $profile?->alt_phone) }}" class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">Optional backup contact number</p>
                                @error('alt_phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="address_line1" value="{{ old('address_line1', $profile?->address_line1) }}" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                            <p class="text-xs text-gray-400 mt-1">Street address, building number</p>
                            @error('address_line1') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address_line2" value="{{ old('address_line2', $profile?->address_line2) }}" class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                            <p class="text-xs text-gray-400 mt-1">Apartment, suite, floor (optional)</p>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="form-label">City</label>
                                <input type="text" name="city" value="{{ old('city', $profile?->city) }}" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">City or town</p>
                                @error('city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">State</label>
                                <input type="text" name="state" value="{{ old('state', $profile?->state) }}" class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">State or province</p>
                            </div>
                            <div>
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">ZIP or postal code</p>
                                @error('postal_code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Country Code</label>
                                <input type="text" name="country" value="{{ old('country', $profile?->country) }}" required maxlength="2" placeholder="BD" class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">2-letter ISO code</p>
                                @error('country') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="form-label">ID Type</label>
                                <select name="id_type" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                    <option value="national_id" {{ old('id_type', $profile?->id_type) === 'national_id' ? 'selected' : '' }}>National ID</option>
                                    <option value="passport" {{ old('id_type', $profile?->id_type) === 'passport' ? 'selected' : '' }}>Passport</option>
                                    <option value="driving_license" {{ old('id_type', $profile?->id_type) === 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                    <option value="business_license" {{ old('id_type', $profile?->id_type) === 'business_license' ? 'selected' : '' }}>Business License</option>
                                </select>
                                <p class="text-xs text-gray-400 mt-1">Government-issued ID type</p>
                                @error('id_type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">ID Number</label>
                                <input type="text" name="id_number" value="{{ old('id_number', $profile?->id_number) }}" required class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">As printed on your ID document</p>
                                @error('id_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">ID Expiry Date</label>
                                <input type="date" name="id_expiry_date" value="{{ old('id_expiry_date', $profile?->id_expiry_date?->format('Y-m-d')) }}" class="form-input" {{ $user->kyc_status === 'approved' ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">Leave blank if no expiry</p>
                                @error('id_expiry_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if($user->kyc_status !== 'approved')
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white rounded-lg {{ $isReseller ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-indigo-600 hover:bg-indigo-700' }} transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $profile ? 'Update & Resubmit' : 'Submit for Verification' }}
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Verification Status --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Verification Status</h3>
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                {{ $profile ? 'bg-emerald-100' : 'bg-gray-100' }}">
                                <svg class="w-4 h-4 {{ $profile ? 'text-emerald-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium {{ $profile ? 'text-gray-900' : 'text-gray-400' }}">Profile Submitted</p>
                                @if($profile?->submitted_at)
                                    <p class="text-xs text-gray-400">{{ $profile->submitted_at->format('M d, Y') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                {{ $documents->isNotEmpty() ? 'bg-emerald-100' : 'bg-gray-100' }}">
                                <svg class="w-4 h-4 {{ $documents->isNotEmpty() ? 'text-emerald-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium {{ $documents->isNotEmpty() ? 'text-gray-900' : 'text-gray-400' }}">Documents Uploaded</p>
                                <p class="text-xs text-gray-400">{{ $documents->count() }} file(s)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                {{ $user->kyc_status === 'approved' ? 'bg-emerald-100' : ($user->kyc_status === 'pending' ? 'bg-amber-100' : 'bg-gray-100') }}">
                                @if($user->kyc_status === 'approved')
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @elseif($user->kyc_status === 'pending')
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium {{ $user->kyc_status === 'approved' ? 'text-gray-900' : 'text-gray-400' }}">Admin Review</p>
                                <p class="text-xs text-gray-400">{{ ucfirst($user->kyc_status) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Document Upload --}}
            @if($profile)
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Documents</h3>
                </div>
                <div class="p-5">
                    {{-- Existing documents --}}
                    @if($documents->isNotEmpty())
                        <div class="space-y-3 mb-5">
                            @foreach($documents as $doc)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ str_replace('_', ' ', ucfirst($doc->document_type)) }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $doc->original_name }}</p>
                                    </div>
                                    <span class="badge {{ ($doc->status ?? 'pending') === 'approved' ? 'badge-success' : (($doc->status ?? 'pending') === 'rejected' ? 'badge-danger' : 'badge-warning') }}">
                                        {{ ucfirst($doc->status ?? 'pending') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Upload form --}}
                    @if($user->kyc_status !== 'approved')
                        <form method="POST" action="{{ route('kyc.upload') }}" enctype="multipart/form-data" class="space-y-4" x-data="{ fileName: '' }">
                            @csrf
                            <div>
                                <label class="form-label">Document Type</label>
                                <select name="document_type" required class="form-input" style="padding-left: 1rem;">
                                    <option value="id_front">ID Front</option>
                                    <option value="id_back">ID Back</option>
                                    <option value="selfie">Selfie with ID</option>
                                    <option value="proof_of_address">Proof of Address</option>
                                    <option value="business_registration">Business Registration</option>
                                    <option value="tax_certificate">Tax Certificate</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">File</label>
                                <label class="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-colors">
                                    <div class="flex items-center gap-3 text-center">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span class="text-sm text-gray-500" x-text="fileName || 'Choose file — JPG, PNG or PDF (max 5MB)'"></span>
                                    </div>
                                    <input type="file" name="document" required accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="fileName = $event.target.files[0]?.name || ''">
                                </label>
                                @error('document') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Upload Document
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-dynamic-component>
