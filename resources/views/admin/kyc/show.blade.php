<x-admin-layout>
    <x-slot name="header">KYC Review: {{ $kycProfile->full_name }}</x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Profile Info --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Profile Information</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">User</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        <a href="{{ route('admin.users.show', $kycProfile->user) }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ $kycProfile->user->name }} ({{ $kycProfile->user->email }})
                        </a>
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Account Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ ucfirst($kycProfile->account_type) }}</dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $kycProfile->full_name }}</dd>
                </div>
                @if($kycProfile->contact_person)
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $kycProfile->contact_person }}</dd>
                </div>
                @endif
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        {{ $kycProfile->phone }}
                        @if($kycProfile->alt_phone)
                            <span class="text-gray-500"> / {{ $kycProfile->alt_phone }}</span>
                        @endif
                    </dd>
                </div>
                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        {{ $kycProfile->address_line1 }}<br>
                        @if($kycProfile->address_line2)
                            {{ $kycProfile->address_line2 }}<br>
                        @endif
                        {{ $kycProfile->city }}, {{ $kycProfile->state }} {{ $kycProfile->postal_code }}<br>
                        {{ $kycProfile->country }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- ID Info --}}
        <div class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Identification</h3>
                </div>
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">ID Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ str_replace('_', ' ', ucfirst($kycProfile->id_type)) }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">ID Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $kycProfile->id_number }}</dd>
                    </div>
                    @if($kycProfile->id_expiry_date)
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Expiry Date</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $kycProfile->id_expiry_date->format('M d, Y') }}</dd>
                    </div>
                    @endif
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Submitted</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $kycProfile->submitted_at?->format('M d, Y H:i') }}</dd>
                    </div>
                    @if($kycProfile->reviewed_at)
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Reviewed</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $kycProfile->reviewed_at->format('M d, Y H:i') }}
                            @if($kycProfile->reviewer)
                                by {{ $kycProfile->reviewer->name }}
                            @endif
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Current Status --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Status</h3>
                </div>
                <div class="p-6">
                    @php $kycStatus = $kycProfile->user->kyc_status; @endphp
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                            {{ $kycStatus === 'approved' ? 'bg-green-100 text-green-800' : ($kycStatus === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($kycStatus === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                            {{ ucfirst($kycStatus) }}
                        </span>
                        @if($kycProfile->user->kyc_rejected_reason)
                            <p class="text-sm text-red-600">Reason: {{ $kycProfile->user->kyc_rejected_reason }}</p>
                        @endif
                    </div>

                    @if($kycStatus === 'pending')
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('admin.kyc.approve', $kycProfile) }}">
                                @csrf
                                <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                    Approve
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.kyc.reject', $kycProfile) }}" x-data="{ showReason: false }">
                                @csrf
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="showReason = !showReason"
                                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                        Reject
                                    </button>
                                    <div x-show="showReason" x-cloak class="flex items-center gap-2">
                                        <input type="text" name="reason" placeholder="Rejection reason..." required
                                               class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-64">
                                        <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                            Confirm
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div class="lg:col-span-2 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Documents ({{ $kycProfile->documents->count() }})</h3>
            </div>
            @if($kycProfile->documents->isEmpty())
                <p class="px-6 py-4 text-sm text-gray-500">No documents uploaded.</p>
            @else
                <div class="divide-y divide-gray-200">
                    @foreach($kycProfile->documents as $doc)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ str_replace('_', ' ', ucfirst($doc->document_type)) }}</p>
                                <p class="text-xs text-gray-500">{{ $doc->original_name }} ({{ number_format($doc->file_size / 1024, 1) }} KB) - {{ $doc->mime_type }}</p>
                                <p class="text-xs text-gray-400">Uploaded {{ $doc->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $doc->status === 'approved' ? 'bg-green-100 text-green-800' : ($doc->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($doc->status ?? 'pending') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.kyc.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">&larr; Back to KYC list</a>
    </div>
</x-admin-layout>
