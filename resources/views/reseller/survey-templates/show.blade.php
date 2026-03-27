<x-reseller-layout>
    <x-slot name="header">Survey Template Details</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">{{ $surveyTemplate->name }}</h2>
            <div class="flex items-center gap-2 mt-1">
                @if($surveyTemplate->status === 'approved')
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>
                @elseif($surveyTemplate->status === 'pending')
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending Approval</span>
                @elseif($surveyTemplate->status === 'rejected')
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ ucfirst($surveyTemplate->status) }}</span>
                @endif
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.survey-templates.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Template Information</h3></div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item"><span class="detail-label">Name</span><span class="detail-value font-semibold">{{ $surveyTemplate->name }}</span></div>
                        <div class="detail-item"><span class="detail-label">Questions</span><span class="detail-value">{{ $surveyTemplate->getQuestionCount() }}</span></div>
                        <div class="detail-item md:col-span-2"><span class="detail-label">Description</span><span class="detail-value">{{ $surveyTemplate->description ?: '—' }}</span></div>
                        <div class="detail-item"><span class="detail-label">Created</span><span class="detail-value">{{ $surveyTemplate->created_at->format('d M Y, H:i') }}</span></div>
                        <div class="detail-item"><span class="detail-label">Updated</span><span class="detail-value">{{ $surveyTemplate->updated_at->format('d M Y, H:i') }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Status</h3></div>
                <div class="detail-card-body">
                    @if($surveyTemplate->status === 'approved')
                        <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>
                            <div><p class="text-sm font-medium text-emerald-800">Approved</p><p class="text-xs text-emerald-600">Ready to use in broadcasts</p></div>
                        </div>
                    @elseif($surveyTemplate->status === 'pending')
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                            <div><p class="text-sm font-medium text-amber-800">Pending Approval</p><p class="text-xs text-amber-600">Waiting for admin review</p></div>
                        </div>
                    @elseif($surveyTemplate->status === 'rejected')
                        <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center"><svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
                            <div><p class="text-sm font-medium text-red-800">Rejected</p><p class="text-xs text-red-600">Contact admin for details</p></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
