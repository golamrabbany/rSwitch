<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">KYC Verification</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status Banner --}}
            @if($user->kyc_status === 'approved')
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                        </svg>
                        <p class="ml-3 text-sm font-medium text-green-800">Your KYC verification has been approved.</p>
                    </div>
                </div>
            @elseif($user->kyc_status === 'rejected')
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">Your KYC verification was rejected.</p>
                            @if($user->kyc_rejected_reason)
                                <p class="mt-1 text-sm text-red-700">Reason: {{ $user->kyc_rejected_reason }}</p>
                            @endif
                            <p class="mt-1 text-sm text-red-700">Please update your information and resubmit.</p>
                        </div>
                    </div>
                </div>
            @elseif($user->kyc_status === 'pending')
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <p class="ml-3 text-sm font-medium text-yellow-800">Your KYC verification is under review.</p>
                    </div>
                </div>
            @endif

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- KYC Profile Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>
                    <p class="mt-1 text-sm text-gray-600">Submit your identity information for verification.</p>
                </div>

                <form method="POST" action="{{ route('kyc.store') }}" class="p-6 space-y-6">
                    @csrf

                    <div>
                        <label for="account_type" class="block text-sm font-medium text-gray-700">Account Type</label>
                        <select id="account_type" name="account_type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="individual" {{ old('account_type', $profile?->account_type) === 'individual' ? 'selected' : '' }}>Individual</option>
                            <option value="company" {{ old('account_type', $profile?->account_type) === 'company' ? 'selected' : '' }}>Company</option>
                        </select>
                        <x-input-error :messages="$errors->get('account_type')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name / Company Name</label>
                            <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $profile?->full_name) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                        </div>
                        <div>
                            <label for="contact_person" class="block text-sm font-medium text-gray-700">Contact Person (if company)</label>
                            <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $profile?->contact_person) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div>
                            <label for="alt_phone" class="block text-sm font-medium text-gray-700">Alternate Phone</label>
                            <input type="text" id="alt_phone" name="alt_phone" value="{{ old('alt_phone', $profile?->alt_phone) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('alt_phone')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <label for="address_line1" class="block text-sm font-medium text-gray-700">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1', $profile?->address_line1) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('address_line1')" class="mt-2" />
                    </div>

                    <div>
                        <label for="address_line2" class="block text-sm font-medium text-gray-700">Address Line 2</label>
                        <input type="text" id="address_line2" name="address_line2" value="{{ old('address_line2', $profile?->address_line2) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('address_line2')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $profile?->city) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('city')" class="mt-2" />
                        </div>
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700">State</label>
                            <input type="text" id="state" name="state" value="{{ old('state', $profile?->state) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('state')" class="mt-2" />
                        </div>
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                        </div>
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700">Country Code</label>
                            <input type="text" id="country" name="country" value="{{ old('country', $profile?->country) }}" required maxlength="2" placeholder="BD"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('country')" class="mt-2" />
                        </div>
                    </div>

                    <hr class="border-gray-200">

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <label for="id_type" class="block text-sm font-medium text-gray-700">ID Type</label>
                            <select id="id_type" name="id_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="national_id" {{ old('id_type', $profile?->id_type) === 'national_id' ? 'selected' : '' }}>National ID</option>
                                <option value="passport" {{ old('id_type', $profile?->id_type) === 'passport' ? 'selected' : '' }}>Passport</option>
                                <option value="driving_license" {{ old('id_type', $profile?->id_type) === 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                <option value="business_license" {{ old('id_type', $profile?->id_type) === 'business_license' ? 'selected' : '' }}>Business License</option>
                            </select>
                            <x-input-error :messages="$errors->get('id_type')" class="mt-2" />
                        </div>
                        <div>
                            <label for="id_number" class="block text-sm font-medium text-gray-700">ID Number</label>
                            <input type="text" id="id_number" name="id_number" value="{{ old('id_number', $profile?->id_number) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('id_number')" class="mt-2" />
                        </div>
                        <div>
                            <label for="id_expiry_date" class="block text-sm font-medium text-gray-700">ID Expiry Date</label>
                            <input type="date" id="id_expiry_date" name="id_expiry_date" value="{{ old('id_expiry_date', $profile?->id_expiry_date?->format('Y-m-d')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('id_expiry_date')" class="mt-2" />
                        </div>
                    </div>

                    @if($user->kyc_status !== 'approved')
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                {{ $profile ? 'Update & Resubmit' : 'Submit for Verification' }}
                            </button>
                        </div>
                    @endif
                </form>
            </div>

            {{-- Document Upload --}}
            @if($profile)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Documents</h3>
                        <p class="mt-1 text-sm text-gray-600">Upload supporting documents (JPG, PNG, or PDF, max 5MB each).</p>
                    </div>

                    <div class="p-6">
                        {{-- Existing documents --}}
                        @if($documents->isNotEmpty())
                            <div class="mb-6 divide-y divide-gray-200 border rounded-md">
                                @foreach($documents as $doc)
                                    <div class="px-4 py-3 flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ str_replace('_', ' ', ucfirst($doc->document_type)) }}</p>
                                            <p class="text-xs text-gray-500">{{ $doc->original_name }} ({{ number_format($doc->file_size / 1024, 1) }} KB)</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ ($doc->status ?? 'pending') === 'approved' ? 'bg-green-100 text-green-800' : (($doc->status ?? 'pending') === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ ucfirst($doc->status ?? 'pending') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Upload form --}}
                        @if($user->kyc_status !== 'approved')
                            <form method="POST" action="{{ route('kyc.upload') }}" enctype="multipart/form-data" class="flex items-end gap-4">
                                @csrf
                                <div class="flex-1">
                                    <label for="document_type" class="block text-sm font-medium text-gray-700">Document Type</label>
                                    <select id="document_type" name="document_type" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="id_front">ID Front</option>
                                        <option value="id_back">ID Back</option>
                                        <option value="selfie">Selfie with ID</option>
                                        <option value="proof_of_address">Proof of Address</option>
                                        <option value="business_registration">Business Registration</option>
                                        <option value="tax_certificate">Tax Certificate</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label for="document" class="block text-sm font-medium text-gray-700">File</label>
                                    <input type="file" id="document" name="document" required accept=".jpg,.jpeg,.png,.pdf"
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                </div>
                                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Upload
                                </button>
                            </form>
                            <x-input-error :messages="$errors->get('document')" class="mt-2" />
                            <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
