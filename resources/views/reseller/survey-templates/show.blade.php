<x-reseller-layout>
    <x-slot name="header">Survey Template: {{ $surveyTemplate->name }}</x-slot>

    <div class="page-header-row">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $surveyTemplate->status === 'approved' ? 'bg-emerald-100' : ($surveyTemplate->status === 'pending' ? 'bg-amber-100' : ($surveyTemplate->status === 'rejected' ? 'bg-red-100' : 'bg-gray-100')) }}">
                @if($surveyTemplate->status === 'approved')
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                @elseif($surveyTemplate->status === 'pending')
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @else
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                @endif
            </div>
            <div>
                <h2 class="page-title">{{ $surveyTemplate->name }}</h2>
                <div class="flex items-center gap-2 mt-0.5">
                    @switch($surveyTemplate->status)
                        @case('approved') <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span> @break
                        @case('pending') <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending</span> @break
                        @case('rejected') <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span> @break
                        @default <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ ucfirst($surveyTemplate->status) }}</span>
                    @endswitch
                    <span class="text-xs text-gray-400">&middot;</span>
                    <span class="text-xs text-gray-500">{{ $surveyTemplate->getQuestionCount() }} {{ Str::plural('question', $surveyTemplate->getQuestionCount()) }}</span>
                    <span class="text-xs text-gray-400">&middot;</span>
                    <span class="text-xs text-gray-500">{{ $surveyTemplate->client?->name }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            @if($surveyTemplate->status === 'pending')
                <a href="{{ route('reseller.survey-templates.edit', $surveyTemplate) }}" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
            @endif
            <a href="{{ route('reseller.survey-templates.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    {{-- Rejection Banner --}}
    @if($surveyTemplate->rejection_reason)
        <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg mb-6">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <p class="text-sm font-semibold text-red-700">Rejected</p>
                <p class="text-sm text-red-600 mt-0.5">{{ $surveyTemplate->rejection_reason }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            {{-- Questions --}}
            @php $config = $surveyTemplate->config ?? ['questions' => []]; $questions = $config['questions'] ?? []; $qNum = 0; @endphp
            @if(count($questions) > 0)
                @foreach($questions as $idx => $q)
                    @if($q['type'] === 'question') @php $qNum++; @endphp @endif
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        {{-- Question Header --}}
                        <div class="px-4 py-2.5 flex items-center justify-between {{ $q['type'] === 'intro' ? 'bg-blue-50 border-b border-blue-100' : 'bg-gray-50 border-b border-gray-100' }}">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg text-xs font-bold text-white flex items-center justify-center {{ $q['type'] === 'intro' ? 'bg-blue-500' : 'bg-emerald-500' }}">
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
                                </div>
                            @endif
                        </div>

                        {{-- Question Body --}}
                        <div class="p-4 space-y-3">
                            {{-- Voice File --}}
                            @if(!empty($q['voice_file_id']))
                                @php $vf = \App\Models\VoiceFile::find($q['voice_file_id']); @endphp
                                @if($vf)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
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
                                    </div>
                                    <audio controls class="w-full" preload="none" style="height:32px;">
                                        <source src="{{ route('reseller.voice-files.play', $vf) }}" type="audio/{{ $vf->format === 'wav' ? 'wav' : 'mpeg' }}">
                                    </audio>
                                @endif
                            @else
                                <div class="flex items-center gap-2 p-3 bg-amber-50 rounded-lg text-sm text-amber-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    No voice file assigned — admin will configure
                                </div>
                            @endif

                            {{-- Response Options --}}
                            @if($q['type'] === 'question' && !empty($q['options']))
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response Options</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($q['options'] as $digit => $label)
                                            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                                                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 text-sm font-bold flex items-center justify-center flex-shrink-0">{{ $digit }}</span>
                                                <span class="text-sm text-gray-700">{{ $label }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="text-sm text-gray-500">No questions configured yet</p>
                    <p class="text-xs text-gray-400 mt-1">Admin will add questions and voice files after review</p>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4" style="position:sticky; top:1rem;">
            {{-- Client --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Client</h3></div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-emerald-600">{{ strtoupper(substr($surveyTemplate->client?->name ?? '?', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $surveyTemplate->client?->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $surveyTemplate->client?->email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Template Info --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Template Info</h3></div>
                <div class="detail-card-body space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $surveyTemplate->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($surveyTemplate->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ ucfirst($surveyTemplate->status) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Questions</span><span class="font-medium text-gray-700">{{ $surveyTemplate->getQuestionCount() }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Has Intro</span><span class="font-medium text-gray-700">{{ collect($config['questions'] ?? [])->contains('type', 'intro') ? 'Yes' : 'No' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="font-medium text-gray-700">{{ $surveyTemplate->created_at->format('d M Y') }}</span></div>
                    @if($surveyTemplate->description)
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-xs text-gray-500">Description</p>
                            <p class="text-sm text-gray-700 mt-0.5">{{ $surveyTemplate->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- How It Works --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">How It Works</h3></div>
                <div class="detail-card-body text-xs text-gray-500 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                        <span>Each question plays a voice file to the caller</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                        <span>Caller presses DTMF digit to respond</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                        <span>Responses are recorded per number</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                        <span>Use this template when creating survey broadcasts</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
