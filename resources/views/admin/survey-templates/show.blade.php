<x-admin-layout>
    <x-slot name="header">Survey Template: {{ $template->name }}</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">{{ $template->name }}</h2>
            <div class="flex items-center gap-2 mt-1">
                @switch($template->status)
                    @case('approved') <span class="badge badge-success">Approved</span> @break
                    @case('pending') <span class="badge badge-warning">Pending Approval</span> @break
                    @case('rejected') <span class="badge badge-danger">Rejected</span> @break
                    @default <span class="badge badge-gray">Draft</span>
                @endswitch
                <span class="text-sm text-gray-500">by {{ $template->user?->name }}</span>
            </div>
        </div>
        <div class="page-actions">
            @if(auth()->user()->isSuperAdmin() && $template->isPending())
                <form method="POST" action="{{ route('admin.survey-templates.approve', $template) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-action-primary-admin bg-emerald-600 hover:bg-emerald-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="btn-action-secondary text-red-600 border-red-300 hover:bg-red-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Reject
                </button>
            @endif
            <a href="{{ route('admin.survey-templates.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Questions --}}
            @php $config = $template->config; $questions = $config['questions'] ?? []; @endphp
            @foreach($questions as $idx => $q)
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">
                            @if($q['type'] === 'intro')
                                <span class="text-blue-600">Welcome / Intro</span>
                            @else
                                {{ $q['label'] ?? 'Question' }}
                            @endif
                        </h3>
                        @if($q['type'] === 'question')
                            <span class="text-xs text-gray-400">{{ count($q['options'] ?? []) }} options</span>
                        @endif
                    </div>
                    <div class="detail-card-body space-y-3">
                        @if(!empty($q['voice_file_id']))
                            @php $vf = \App\Models\VoiceFile::find($q['voice_file_id']); @endphp
                            @if($vf)
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">{{ $vf->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $vf->duration ?? '?' }}s &middot; {{ strtoupper($vf->format) }}</p>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($q['type'] === 'question' && !empty($q['options']))
                            <div class="space-y-1.5">
                                @foreach($q['options'] as $digit => $label)
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded bg-indigo-100 text-indigo-600 text-sm font-bold flex items-center justify-center">{{ $digit }}</span>
                                        <span class="text-sm text-gray-700">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex gap-4 text-xs text-gray-400 pt-1">
                                <span>Max digits: {{ $q['max_digits'] ?? 1 }}</span>
                                <span>Timeout: {{ $q['timeout'] ?? 10 }}s</span>
                                <span>Retries: {{ $q['max_retries'] ?? 2 }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Details</h3></div>
                <div class="detail-card-body space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Client</span><span class="font-medium">{{ $template->client?->name ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Questions</span><span class="font-medium">{{ $template->getQuestionCount() }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="font-medium">{{ $template->created_at->format('M d, Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Created By</span><span class="font-medium">{{ $template->user?->name }}</span></div>
                    @if($template->approved_at)
                        <div class="flex justify-between"><span class="text-gray-500">Approved</span><span class="font-medium">{{ $template->approved_at->format('M d, Y') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Approved By</span><span class="font-medium">{{ $template->approvedBy?->name }}</span></div>
                    @endif
                    @if($template->rejection_reason)
                        <div class="mt-3 p-3 bg-red-50 rounded-lg">
                            <p class="text-xs font-medium text-red-700">Rejection Reason:</p>
                            <p class="text-sm text-red-600 mt-1">{{ $template->rejection_reason }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    @if(auth()->user()->isSuperAdmin() && $template->isPending())
        <div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Template</h3>
                <form method="POST" action="{{ route('admin.survey-templates.reject', $template) }}">
                    @csrf
                    <textarea name="rejection_reason" class="form-input w-full" rows="3" placeholder="Reason for rejection..." required></textarea>
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="btn-action-secondary">Cancel</button>
                        <button type="submit" class="btn-action-primary-admin bg-red-600 hover:bg-red-700">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-admin-layout>
