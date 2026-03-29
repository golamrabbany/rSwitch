<x-client-layout>
    <x-slot name="header">Create Broadcast</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Create Broadcast</h2>
            <p class="page-subtitle">Set up a new voice broadcast campaign</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.broadcasts.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <script>var _sipChannels = { @foreach($sipAccounts as $sip){{ $sip->id }}: {{ $sip->max_channels ?? 5 }},@endforeach };</script>

    <form method="POST" action="{{ route('client.broadcasts.store') }}" enctype="multipart/form-data"
          x-data="{
              type: '{{ old('type', 'simple') }}',
              phoneListType: '{{ old('phone_list_type', 'manual') }}',
              scheduleType: '{{ old('schedule_type', 'now') }}'
          }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Details</h3>
                        <p class="form-card-subtitle">Select a template and configure settings</p>
                    </div>
                    <div class="form-card-body">
                        <div class="space-y-4">
                            {{-- Name + Type Row --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Broadcast Name</label>
                                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. March Promo Campaign">
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Broadcast Type</label>
                                    <div class="flex gap-3 mt-1">
                                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                               :class="type === 'simple' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                            <input type="radio" name="type" value="simple" x-model="type" class="sr-only">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                            <span class="font-medium text-sm">Simple</span>
                                        </label>
                                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                               :class="type === 'survey' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                            <input type="radio" name="type" value="survey" x-model="type" class="sr-only">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                            <span class="font-medium text-sm">Survey</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Voice Template (Simple) --}}
                            <div class="form-group" x-show="type === 'simple'" x-transition>
                                <label class="form-label">Voice Template</label>
                                <select name="voice_file_id" class="form-input">
                                    <option value="">Select Voice Template</option>
                                    @foreach($voiceFiles as $vf)
                                        <option value="{{ $vf->id }}" {{ old('voice_file_id') == $vf->id ? 'selected' : '' }}>{{ $vf->name }} ({{ strtoupper($vf->format) }})</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Your approved voice templates</p>
                                <x-input-error :messages="$errors->get('voice_file_id')" class="mt-2" />
                            </div>

                            {{-- Survey Template (Survey) --}}
                            <div class="form-group" x-show="type === 'survey'" x-transition>
                                <label class="form-label">Survey Template</label>
                                <select name="survey_template_id" class="form-input">
                                    <option value="">Select Survey Template</option>
                                    @foreach($surveyTemplates as $st)
                                        <option value="{{ $st->id }}" {{ old('survey_template_id') == $st->id ? 'selected' : '' }}>{{ $st->name }} ({{ $st->getQuestionCount() }} questions)</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Your approved survey templates</p>
                                <x-input-error :messages="$errors->get('survey_template_id')" class="mt-2" />
                            </div>

                            {{-- SIP Account --}}
                            <div class="form-group">
                                <label class="form-label">SIP Account</label>
                                <select name="sip_account_id" required class="form-input"
                                        @change="let ch = _sipChannels[$event.target.value]; if (ch) { $refs.maxConcurrent.value = ch; }">
                                    <option value="">Select SIP Account</option>
                                    @foreach($sipAccounts as $sip)
                                        <option value="{{ $sip->id }}" {{ old('sip_account_id') == $sip->id ? 'selected' : '' }}>{{ $sip->username }}{{ $sip->max_channels ? ' ('.$sip->max_channels.' ch)' : '' }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('sip_account_id')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Schedule</h3>
                        <p class="form-card-subtitle">When to start the broadcast</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="flex gap-3">
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'now' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                <input type="radio" name="schedule_type" value="now" x-model="scheduleType" class="sr-only">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span class="text-sm font-medium">Start Manually</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'scheduled' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                <input type="radio" name="schedule_type" value="scheduled" x-model="scheduleType" class="sr-only">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-sm font-medium">Schedule</span>
                            </label>
                        </div>
                        <div x-show="scheduleType === 'now'" x-transition class="text-sm text-gray-500">
                            <p>Broadcast will be created as <strong>draft</strong>. Start it manually from the detail page.</p>
                        </div>
                        <div x-show="scheduleType === 'scheduled'" x-transition>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="form-group"><label class="form-label">Date</label><input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" class="form-input" min="{{ now()->format('Y-m-d') }}"></div>
                                <div class="form-group"><label class="form-label">Time</label><input type="time" name="scheduled_time" value="{{ old('scheduled_time') }}" class="form-input"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Phone Numbers --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Phone Numbers</h3>
                        <p class="form-card-subtitle">Provide the list of numbers to call</p>
                    </div>
                    <div class="form-card-body">
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Input Method</label>
                                <div class="flex gap-4 mt-1">
                                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                           :class="phoneListType === 'manual' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                        <input type="radio" name="phone_list_type" value="manual" x-model="phoneListType" class="sr-only">
                                        <span class="font-medium text-sm">Manual Entry</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                           :class="phoneListType === 'csv' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                        <input type="radio" name="phone_list_type" value="csv" x-model="phoneListType" class="sr-only">
                                        <span class="font-medium text-sm">CSV Upload</span>
                                    </label>
                                </div>
                            </div>

                            <div x-show="phoneListType === 'manual'" x-transition class="form-group">
                                <label class="form-label">Phone Numbers</label>
                                <textarea name="phone_numbers" rows="6" class="form-input" placeholder="Enter one number per line&#10;e.g.&#10;8801712345678&#10;8801798765432">{{ old('phone_numbers') }}</textarea>
                                <p class="form-hint">Enter one phone number per line.</p>
                                <x-input-error :messages="$errors->get('phone_numbers')" class="mt-2" />
                            </div>

                            <div x-show="phoneListType === 'csv'" x-transition class="form-group">
                                <label class="form-label">CSV File</label>
                                <input type="file" name="csv_file" accept=".csv,.txt" class="form-input">
                                <p class="form-hint">Upload a CSV file with one phone number per row.</p>
                                <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Call Settings (hidden, auto-set from SIP account) --}}
                <input type="hidden" name="max_concurrent" x-ref="maxConcurrent" value="{{ old('max_concurrent', 5) }}">
                <input type="hidden" name="ring_timeout" value="{{ old('ring_timeout', 30) }}">

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('client.broadcasts.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" name="action" value="draft" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save Draft
                    </button>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Create Broadcast
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">How It Works</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">1</span>
                                </div>
                                <p class="text-sm text-gray-600">Select type & pick a template</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">2</span>
                                </div>
                                <p class="text-sm text-gray-600">Add phone numbers (manual or CSV)</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">3</span>
                                </div>
                                <p class="text-sm text-gray-600">Start the broadcast to begin dialing</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-emerald-600">4</span>
                                </div>
                                <p class="text-sm text-gray-600">Monitor progress and view results</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Tips</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Upload voice templates first, then create broadcasts</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Use 5-10 concurrent calls for best results</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Ensure you have sufficient balance before starting</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-client-layout>
