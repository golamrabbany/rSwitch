<x-client-layout>
    <x-slot name="header">{{ $surveyTemplate->name }}</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">{{ $surveyTemplate->name }}</h2>
            <p class="page-subtitle">
                @switch($surveyTemplate->status)
                    @case('approved')
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span> @break
                    @case('pending')
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending Approval</span> @break
                    @case('rejected')
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span> @break
                    @default
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ ucfirst($surveyTemplate->status) }}</span>
                @endswitch
                &middot; {{ $surveyTemplate->getQuestionCount() }} questions
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.survey-templates.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if($surveyTemplate->description)
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Description</h3></div>
                    <div class="detail-card-body"><p class="text-sm text-gray-700">{{ $surveyTemplate->description }}</p></div>
                </div>
            @endif

            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Questions</h3></div>
                <div class="detail-card-body p-0 divide-y divide-gray-100">
                    @foreach($surveyTemplate->getSurveyQuestions() as $i => $q)
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold {{ $q['type'] === 'intro' ? 'bg-blue-100 text-blue-700' : 'bg-indigo-100 text-indigo-700' }}">
                                    {{ $q['type'] === 'intro' ? 'I' : $i }}
                                </span>
                                <span class="text-sm font-semibold text-gray-900">{{ $q['label'] ?: ($q['type'] === 'intro' ? 'Introduction' : 'Question ' . $i) }}</span>
                                <span class="text-xs text-gray-400">({{ $q['type'] }})</span>
                            </div>
                            @if(!empty($q['options']))
                                <div class="ml-8 space-y-1">
                                    @foreach($q['options'] as $digit => $label)
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-100 text-xs font-mono font-bold text-gray-600">{{ $digit }}</span>
                                            <span class="text-gray-700">{{ $label }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Details</h3></div>
                <div class="detail-card-body">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center py-1 border-b border-gray-100">
                            <span class="text-gray-500">Questions</span>
                            <span class="font-semibold text-gray-900">{{ $surveyTemplate->getQuestionCount() }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-gray-100">
                            <span class="text-gray-500">Created</span>
                            <span class="text-gray-900">{{ $surveyTemplate->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-500">Created By</span>
                            <span class="text-gray-900">{{ $surveyTemplate->user?->name ?? 'Admin' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
