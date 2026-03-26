<x-admin-layout>
    <x-slot name="header">Survey Template: {{ $template->name }}</x-slot>

    <div class="page-header-row">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $template->isApproved() ? 'bg-emerald-100' : ($template->isPending() ? 'bg-amber-100' : ($template->status === 'rejected' ? 'bg-red-100' : 'bg-gray-100')) }}">
                @if($template->isApproved())
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                @elseif($template->isPending())
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @else
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                @endif
            </div>
            <div>
                <h2 class="page-title">{{ $template->name }}</h2>
                <div class="flex items-center gap-2 mt-0.5">
                    @switch($template->status)
                        @case('approved')
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>
                            @break
                        @case('pending')
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending Approval</span>
                            @break
                        @case('rejected')
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span>
                            @break
                        @case('suspended')
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Suspended</span>
                            @break
                        @default
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Draft</span>
                    @endswitch
                    <span class="text-xs text-gray-400">&middot;</span>
                    <span class="text-xs text-gray-500">{{ $template->getQuestionCount() }} {{ Str::plural('question', $template->getQuestionCount()) }}</span>
                    <span class="text-xs text-gray-400">&middot;</span>
                    <span class="text-xs text-gray-500">{{ $template->client?->name }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            @if(auth()->user()->isSuperAdmin())
                {{-- Edit --}}
                <a href="{{ route('admin.survey-templates.edit', $template) }}" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>

                {{-- Approve (if not already approved) --}}
                @if($template->status !== 'approved')
                    <form method="POST" action="{{ route('admin.survey-templates.approve', $template) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-action-primary-admin bg-emerald-600 hover:bg-emerald-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Approve
                        </button>
                    </form>
                @endif

                {{-- Reject (if not already rejected) --}}
                @if($template->status !== 'rejected')
                    <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="btn-action-secondary text-red-600 border-red-300 hover:bg-red-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject
                    </button>
                @endif

                {{-- Suspend (if approved) --}}
                @if($template->status === 'approved')
                    <form method="POST" action="{{ route('admin.survey-templates.suspend', $template) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-action-secondary text-amber-600 border-amber-300 hover:bg-amber-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Suspend
                        </button>
                    </form>
                @endif

                {{-- Set Pending (if rejected or suspended) --}}
                @if(in_array($template->status, ['rejected', 'suspended']))
                    <form method="POST" action="{{ route('admin.survey-templates.set-pending', $template) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-action-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Set Pending
                        </button>
                    </form>
                @endif
            @endif

            <a href="{{ route('admin.survey-templates.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    {{-- Rejection Banner --}}
    @if($template->rejection_reason)
        <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg mb-6">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <p class="text-sm font-semibold text-red-700">Rejected</p>
                <p class="text-sm text-red-600 mt-0.5">{{ $template->rejection_reason }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            {{-- Questions --}}
            @php $config = $template->config; $questions = $config['questions'] ?? []; $qNum = 0; @endphp
            @foreach($questions as $idx => $q)
                @if($q['type'] === 'question') @php $qNum++; @endphp @endif
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    {{-- Question Header --}}
                    <div class="px-4 py-2.5 flex items-center justify-between {{ $q['type'] === 'intro' ? 'bg-blue-50 border-b border-blue-100' : 'bg-gray-50 border-b border-gray-100' }}">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg text-xs font-bold text-white flex items-center justify-center {{ $q['type'] === 'intro' ? 'bg-blue-500' : 'bg-indigo-500' }}">
                                {{ $q['type'] === 'intro' ? '♫' : $qNum }}
                            </span>
                            <div>
                                <span class="text-sm font-semibold {{ $q['type'] === 'intro' ? 'text-blue-700' : 'text-gray-800' }}">
                                    {{ $q['type'] === 'intro' ? 'Welcome / Intro' : ($q['label'] ?? 'Question '.$qNum) }}
                                </span>
                                @if($q['type'] === 'intro')
                                    <span class="text-xs text-blue-400 ml-1">- plays first, no DTMF</span>
                                @endif
                            </div>
                        </div>
                        @if($q['type'] === 'question')
                            <div class="flex items-center gap-3 text-xs text-gray-400">
                                <span>{{ count($q['options'] ?? []) }} options</span>
                                <span>Digits: {{ $q['max_digits'] ?? 1 }}</span>
                                <span>Timeout: {{ $q['timeout'] ?? 10 }}s</span>
                                <span>Retries: {{ $q['max_retries'] ?? 2 }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Question Body --}}
                    <div class="p-4 space-y-3">
                        {{-- Voice File + Audio Player --}}
                        @if(!empty($q['voice_file_id']))
                            @php $vf = \App\Models\VoiceFile::find($q['voice_file_id']); @endphp
                            @if($vf)
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-700 truncate">{{ $vf->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $vf->duration ?? '?' }}s &middot; {{ strtoupper($vf->format) }} &middot;
                                            @if($vf->status === 'approved')
                                                <span class="text-emerald-500">Approved</span>
                                            @elseif($vf->status === 'pending')
                                                <span class="text-amber-500">Pending</span>
                                            @else
                                                <span class="text-red-500">{{ ucfirst($vf->status) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.voice-files.download', $vf) }}" class="p-1.5 text-gray-400 hover:text-indigo-600 rounded transition-colors" title="Download">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                </div>
                                <audio controls class="w-full" preload="none" style="height:32px;">
                                    <source src="{{ route('admin.voice-files.play', $vf) }}" type="audio/{{ $vf->format === 'wav' ? 'wav' : 'mpeg' }}">
                                </audio>
                            @else
                                <div class="flex items-center gap-2 p-3 bg-red-50 rounded-lg text-sm text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Voice file not found (ID: {{ $q['voice_file_id'] }})
                                </div>
                            @endif
                        @else
                            <div class="flex items-center gap-2 p-3 bg-amber-50 rounded-lg text-sm text-amber-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                No voice file assigned
                            </div>
                        @endif

                        {{-- Response Options --}}
                        @if($q['type'] === 'question' && !empty($q['options']))
                            <div class="space-y-1.5 pt-1">
                                @foreach($q['options'] as $digit => $label)
                                    <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg">
                                        <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 text-sm font-bold flex items-center justify-center flex-shrink-0">{{ $digit }}</span>
                                        <span class="text-sm text-gray-700">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Intro description --}}
                        @if($q['type'] === 'intro' && !empty($q['label']))
                            <p class="text-sm text-gray-500 italic">{{ $q['label'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4" style="position:sticky; top:1rem;">
            {{-- Template Info --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Template Details</h3></div>
                <div class="detail-card-body space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Client</span><span class="font-medium text-gray-900">{{ $template->client?->name ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Questions</span><span class="font-medium text-gray-900">{{ $template->getQuestionCount() }}</span></div>
                    @if($template->getIntro())
                        <div class="flex justify-between"><span class="text-gray-500">Has Intro</span><span class="font-medium text-emerald-600">Yes</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-500">Status</span>
                        <span class="font-medium {{ $template->isApproved() ? 'text-emerald-600' : ($template->isPending() ? 'text-amber-600' : ($template->status === 'rejected' ? 'text-red-600' : 'text-gray-500')) }}">{{ ucfirst($template->status) }}</span>
                    </div>
                    @if($template->description)
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-xs text-gray-400 mb-1">Description</p>
                            <p class="text-gray-600">{{ $template->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Timeline --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Activity</h3></div>
                <div class="detail-card-body text-sm space-y-3">
                    <div class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="text-gray-700">Created by <span class="font-medium">{{ $template->user?->name }}</span></p>
                            <p class="text-xs text-gray-400">{{ $template->created_at->format('M d, Y g:i A') }}</p>
                        </div>
                    </div>
                    @if($template->approved_at)
                        <div class="flex items-start gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 mt-1.5 flex-shrink-0"></div>
                            <div>
                                <p class="text-gray-700">Approved by <span class="font-medium">{{ $template->approvedBy?->name }}</span></p>
                                <p class="text-xs text-gray-400">{{ $template->approved_at->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($template->rejection_reason)
                        <div class="flex items-start gap-2">
                            <div class="w-2 h-2 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></div>
                            <div>
                                <p class="text-gray-700">Rejected</p>
                                <p class="text-xs text-gray-400">{{ $template->updated_at->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Usage Stats --}}
            @php $broadcastCount = $template->broadcasts()->count(); @endphp
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Usage</h3></div>
                <div class="detail-card-body text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Broadcasts using this</span>
                        <span class="font-bold text-gray-900">{{ $broadcastCount }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    @if(auth()->user()->isSuperAdmin() && $template->status !== 'rejected')
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
