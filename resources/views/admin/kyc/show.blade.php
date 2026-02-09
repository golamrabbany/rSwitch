<x-admin-layout>
    <x-slot name="header">KYC Review</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">{{ $kycProfile->full_name }}</h2>
            <p class="page-subtitle">KYC verification review for {{ $kycProfile->user->email }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.kyc.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    @php $kycStatus = $kycProfile->user->kyc_status; @endphp
    <div class="kyc-status-banner kyc-status-{{ $kycStatus }}">
        <div class="kyc-status-banner-content">
            <div class="kyc-status-banner-icon">
                @if($kycStatus === 'approved')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($kycStatus === 'pending')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($kycStatus === 'rejected')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
            </div>
            <div class="kyc-status-banner-text">
                <span class="kyc-status-banner-title">KYC Status: {{ ucfirst($kycStatus) }}</span>
                @if($kycProfile->user->kyc_rejected_reason)
                    <span class="kyc-status-banner-reason">Reason: {{ $kycProfile->user->kyc_rejected_reason }}</span>
                @endif
                @if($kycProfile->reviewed_at)
                    <span class="kyc-status-banner-meta">
                        Reviewed {{ $kycProfile->reviewed_at->format('M d, Y H:i') }}
                        @if($kycProfile->reviewer) by {{ $kycProfile->reviewer->name }} @endif
                    </span>
                @endif
            </div>
        </div>
        @if($kycStatus === 'pending')
            <div class="kyc-status-banner-actions">
                <form method="POST" action="{{ route('admin.kyc.approve', $kycProfile) }}">
                    @csrf
                    <button type="submit" class="btn-success">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Approve
                    </button>
                </form>
                <div x-data="{ showReject: false }">
                    <button type="button" @click="showReject = !showReject" class="btn-action-secondary btn-reject-toggle">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Reject
                    </button>
                    <div x-show="showReject" x-cloak class="kyc-reject-form">
                        <form method="POST" action="{{ route('admin.kyc.reject', $kycProfile) }}" class="kyc-reject-form-inner">
                            @csrf
                            <input type="text" name="reason" placeholder="Rejection reason..." required class="form-input">
                            <button type="submit" class="btn-danger-sm">Confirm Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Profile Information --}}
        <div class="detail-card lg:col-span-2">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Profile Information</h3>
            </div>
            <div class="detail-card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">User Account</span>
                        <a href="{{ route('admin.users.show', $kycProfile->user) }}" class="detail-value text-indigo-600 hover:text-indigo-700">
                            {{ $kycProfile->user->name }} ({{ $kycProfile->user->email }})
                        </a>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Account Type</span>
                        <span class="detail-value">
                            <span class="badge {{ $kycProfile->account_type === 'company' ? 'badge-blue' : 'badge-gray' }}">
                                {{ ucfirst($kycProfile->account_type) }}
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value">{{ $kycProfile->full_name }}</span>
                    </div>
                    @if($kycProfile->contact_person)
                        <div class="detail-item">
                            <span class="detail-label">Contact Person</span>
                            <span class="detail-value">{{ $kycProfile->contact_person }}</span>
                        </div>
                    @endif
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">
                            {{ $kycProfile->phone }}
                            @if($kycProfile->alt_phone)
                                <span class="text-gray-400"> / {{ $kycProfile->alt_phone }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Submitted</span>
                        <span class="detail-value">{{ $kycProfile->submitted_at?->format('M d, Y H:i') ?? '—' }}</span>
                    </div>
                </div>

                <div class="kyc-address-section">
                    <span class="detail-label">Address</span>
                    <div class="kyc-address-box">
                        <p>{{ $kycProfile->address_line1 }}</p>
                        @if($kycProfile->address_line2)
                            <p>{{ $kycProfile->address_line2 }}</p>
                        @endif
                        <p>{{ $kycProfile->city }}, {{ $kycProfile->state }} {{ $kycProfile->postal_code }}</p>
                        <p class="font-medium">{{ $kycProfile->country }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Identification --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Identification</h3>
            </div>
            <div class="detail-card-body">
                <div class="space-y-4">
                    <div class="detail-item">
                        <span class="detail-label">ID Type</span>
                        <span class="detail-value">{{ str_replace('_', ' ', ucfirst($kycProfile->id_type)) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">ID Number</span>
                        <span class="detail-value font-mono">{{ $kycProfile->id_number }}</span>
                    </div>
                    @if($kycProfile->id_expiry_date)
                        <div class="detail-item">
                            <span class="detail-label">Expiry Date</span>
                            <span class="detail-value {{ $kycProfile->id_expiry_date->isPast() ? 'text-red-600' : '' }}">
                                {{ $kycProfile->id_expiry_date->format('M d, Y') }}
                                @if($kycProfile->id_expiry_date->isPast())
                                    <span class="badge badge-danger ml-2">Expired</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Documents --}}
    <div class="detail-card mt-6">
        <div class="detail-card-header">
            <h3 class="detail-card-title">Documents ({{ $kycProfile->documents->count() }})</h3>
        </div>
        @if($kycProfile->documents->isEmpty())
            <div class="detail-card-body">
                <div class="empty-state py-8">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="empty-text">No documents uploaded</p>
                </div>
            </div>
        @else
            <div class="kyc-documents-grid">
                @foreach($kycProfile->documents as $doc)
                    <div class="kyc-document-card">
                        <div class="kyc-document-icon">
                            @if(Str::startsWith($doc->mime_type, 'image/'))
                                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            @elseif($doc->mime_type === 'application/pdf')
                                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            @else
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="kyc-document-info">
                            <div class="kyc-document-type">{{ str_replace('_', ' ', ucfirst($doc->document_type)) }}</div>
                            <div class="kyc-document-name">{{ $doc->original_name }}</div>
                            <div class="kyc-document-meta">
                                {{ number_format($doc->file_size / 1024, 1) }} KB
                                <span class="mx-1">·</span>
                                {{ $doc->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        <div class="kyc-document-status">
                            @if($doc->status === 'accepted')
                                <span class="badge badge-success">Accepted</span>
                            @elseif($doc->status === 'rejected')
                                <span class="badge badge-danger">Rejected</span>
                            @else
                                <span class="badge badge-warning">Uploaded</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
