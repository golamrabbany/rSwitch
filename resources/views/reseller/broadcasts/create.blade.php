<x-reseller-layout>
    <x-slot name="header">Create Broadcast</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Create Broadcast</h2>
            <p class="page-subtitle">Set up a new voice broadcast campaign</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.broadcasts.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to List
            </a>
        </div>
    </div>

    <script>
        var _voiceTemplates = @json($voiceTemplatesJson);
        var _surveyTemplates = @json($surveyTemplatesJson);
    </script>

    <form method="POST" action="{{ route('reseller.broadcasts.store') }}" enctype="multipart/form-data"
          x-data="{
              type: '{{ old('type', 'simple') }}',
              phoneListType: '{{ old('phone_list_type', 'manual') }}',
              scheduleType: '{{ old('schedule_type', 'now') }}',

              vtOpen: false, vtSearch: '', vtResults: [], selectedTemplateId: '',
              stOpen: false, stSearch: '', stResults: [], selectedSurveyId: '',

              clientName: '', clientEmail: '', clientBalance: 0,
              sipAccounts: [], loading: false,

              searchVoiceTemplates() {
                  if (!this.vtSearch || this.vtSearch.length < 1) { this.vtResults = _voiceTemplates.slice(0, 10); return; }
                  var q = this.vtSearch.toLowerCase();
                  this.vtResults = _voiceTemplates.filter(function(t) { return t.name.toLowerCase().indexOf(q) > -1 || t.client.toLowerCase().indexOf(q) > -1; }).slice(0, 10);
              },
              selectVoiceTemplate(t) { this.vtSearch = t.name + ' — ' + t.client; this.selectedTemplateId = t.id; this.vtOpen = false; this.loadTemplateData(); },
              clearVoiceTemplate() { this.vtSearch = ''; this.selectedTemplateId = ''; this.vtResults = []; this.clientName = ''; this.sipAccounts = []; this.$nextTick(() => this.$refs.vtInput.focus()); },

              searchSurveyTemplates() {
                  if (!this.stSearch || this.stSearch.length < 1) { this.stResults = _surveyTemplates.slice(0, 10); return; }
                  var q = this.stSearch.toLowerCase();
                  this.stResults = _surveyTemplates.filter(function(t) { return t.name.toLowerCase().indexOf(q) > -1 || t.client.toLowerCase().indexOf(q) > -1; }).slice(0, 10);
              },
              selectSurveyTemplate(t) { this.stSearch = t.name + ' — ' + t.client; this.selectedSurveyId = t.id; this.stOpen = false; this.loadTemplateData(); },
              clearSurveyTemplate() { this.stSearch = ''; this.selectedSurveyId = ''; this.stResults = []; this.clientName = ''; this.sipAccounts = []; this.$nextTick(() => this.$refs.stInput.focus()); },

              loadTemplateData() {
                  let type = this.type === 'simple' ? 'voice' : 'survey';
                  let templateId = this.type === 'simple' ? this.selectedTemplateId : this.selectedSurveyId;
                  if (!templateId) { this.clientName = ''; this.sipAccounts = []; return; }
                  this.loading = true;
                  fetch('{{ route('reseller.broadcasts.template-data') }}?type=' + type + '&template_id=' + templateId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                  .then(r => r.json())
                  .then(data => { this.clientName = data.client.name; this.clientEmail = data.client.email; this.clientBalance = data.client.balance || 0; this.sipAccounts = data.sip_accounts || []; this.loading = false; })
                  .catch(() => { this.loading = false; });
              },
              switchType() { this.clientName = ''; this.clientEmail = ''; this.clientBalance = 0; this.sipAccounts = []; if (this.type === 'simple') { this.stSearch = ''; this.selectedSurveyId = ''; } else { this.vtSearch = ''; this.selectedTemplateId = ''; } }
          }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Details</h3>
                        <p class="form-card-subtitle">Select a template — client and SIP accounts auto-fill</p>
                    </div>
                    <div class="form-card-body">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Broadcast Name</label>
                                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. March Promo Campaign">
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Broadcast Type</label>
                                    <div class="flex gap-3 mt-1">
                                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="type === 'simple' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                            <input type="radio" name="type" value="simple" x-model="type" @change="switchType()" class="sr-only">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6"/></svg>
                                            <span class="font-medium text-sm">Simple</span>
                                        </label>
                                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="type === 'survey' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                            <input type="radio" name="type" value="survey" x-model="type" @change="switchType()" class="sr-only">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                            <span class="font-medium text-sm">Survey</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Voice Template Search --}}
                            <div class="form-group" x-show="type === 'simple'" x-transition>
                                <label class="form-label">Voice Template</label>
                                <div class="relative" @click.outside="vtOpen = false">
                                    <input type="hidden" name="voice_file_id" :value="selectedTemplateId">
                                    <div class="relative">
                                        <input type="text" x-ref="vtInput" x-model="vtSearch" @input="vtOpen = true; selectedTemplateId = ''; searchVoiceTemplates()" @focus="vtOpen = true; searchVoiceTemplates()" @keydown.escape="vtOpen = false" class="form-input pr-16" placeholder="Search template by name or client..." autocomplete="off">
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                            <button type="button" x-show="vtSearch" x-cloak @click="clearVoiceTemplate()" class="p-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                    </div>
                                    <div x-show="vtOpen && vtResults.length > 0" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                        <template x-for="t in vtResults" :key="t.id">
                                            <div @click="selectVoiceTemplate(t)" class="px-4 py-2.5 cursor-pointer hover:bg-emerald-50 flex items-center gap-3 border-b border-gray-50">
                                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6"/></svg></div>
                                                <div><p class="text-sm font-medium text-gray-900" x-text="t.name"></p><p class="text-xs text-gray-500" x-text="t.client + ' · ' + t.format + (t.duration ? ' · ' + t.duration + 's' : '')"></p></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <p class="form-hint">Search by template name or client name</p>
                                <x-input-error :messages="$errors->get('voice_file_id')" class="mt-2" />
                            </div>

                            {{-- Survey Template Search --}}
                            <div class="form-group" x-show="type === 'survey'" x-transition>
                                <label class="form-label">Survey Template</label>
                                <div class="relative" @click.outside="stOpen = false">
                                    <input type="hidden" name="survey_template_id" :value="selectedSurveyId">
                                    <div class="relative">
                                        <input type="text" x-ref="stInput" x-model="stSearch" @input="stOpen = true; selectedSurveyId = ''; searchSurveyTemplates()" @focus="stOpen = true; searchSurveyTemplates()" @keydown.escape="stOpen = false" class="form-input pr-16" placeholder="Search template by name or client..." autocomplete="off">
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                            <button type="button" x-show="stSearch" x-cloak @click="clearSurveyTemplate()" class="p-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                    </div>
                                    <div x-show="stOpen && stResults.length > 0" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                        <template x-for="t in stResults" :key="t.id">
                                            <div @click="selectSurveyTemplate(t)" class="px-4 py-2.5 cursor-pointer hover:bg-emerald-50 flex items-center gap-3 border-b border-gray-50">
                                                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center"><svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                                                <div><p class="text-sm font-medium text-gray-900" x-text="t.name"></p><p class="text-xs text-gray-500" x-text="t.client + ' · ' + t.questions + ' questions'"></p></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <p class="form-hint">Search by template name or client name</p>
                                <x-input-error :messages="$errors->get('survey_template_id')" class="mt-2" />
                            </div>

                            {{-- Client Info --}}
                            <div x-show="clientName" x-cloak x-transition class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center"><span class="text-xs font-bold text-emerald-600" x-text="clientName.substring(0, 2).toUpperCase()"></span></div>
                                    <div><p class="text-sm font-medium text-emerald-800" x-text="'Client: ' + clientName"></p><p class="text-xs text-emerald-600" x-text="clientEmail"></p></div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-mono font-semibold" :class="clientBalance > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + clientBalance.toFixed(2)"></p>
                                    <p class="text-xs text-gray-500">Balance</p>
                                </div>
                            </div>

                            {{-- SIP Account --}}
                            <div class="form-group">
                                <label class="form-label">SIP Account</label>
                                <select name="sip_account_id" required class="form-input" @change="let sip = sipAccounts.find(s => s.id == $event.target.value); if (sip && sip.max_channels) { $refs.maxConcurrent.value = sip.max_channels; $refs.maxConcurrentHidden.value = sip.max_channels; }">
                                    <option value="">Select SIP Account</option>
                                    <template x-for="sip in sipAccounts" :key="sip.id">
                                        <option :value="sip.id" x-text="sip.username + (sip.max_channels ? ' (' + sip.max_channels + ' ch)' : '')"></option>
                                    </template>
                                </select>
                                <p class="form-hint">Auto-populated from the selected template's client</p>
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
                    <div class="form-card-body">
                        <div class="form-group">
                            <div class="flex gap-3">
                                <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'now' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <input type="radio" name="schedule_type" value="now" x-model="scheduleType" class="sr-only">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span class="font-medium text-sm">Start Manually</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'scheduled' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <input type="radio" name="schedule_type" value="scheduled" x-model="scheduleType" class="sr-only">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="font-medium text-sm">Schedule for Later</span>
                                </label>
                            </div>
                        </div>
                        <div x-show="scheduleType === 'now'" x-transition class="text-sm text-gray-500">
                            <p>Broadcast will be created as <strong>draft</strong>. Start it manually from the detail page.</p>
                        </div>
                        <div x-show="scheduleType === 'scheduled'" x-transition>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group"><label class="form-label">Date</label><input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" class="form-input" min="{{ now()->format('Y-m-d') }}"></div>
                                <div class="form-group"><label class="form-label">Time</label><input type="time" name="scheduled_time" value="{{ old('scheduled_time') }}" class="form-input"></div>
                            </div>
                            <p class="form-hint mt-2">Server timezone: {{ config('app.timezone') }}</p>
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
                                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="phoneListType === 'manual' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                        <input type="radio" name="phone_list_type" value="manual" x-model="phoneListType" class="sr-only"><span class="font-medium text-sm">Manual Entry</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="phoneListType === 'csv' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                        <input type="radio" name="phone_list_type" value="csv" x-model="phoneListType" class="sr-only"><span class="font-medium text-sm">CSV Upload</span>
                                    </label>
                                </div>
                            </div>
                            <div x-show="phoneListType === 'manual'" x-transition class="form-group">
                                <label class="form-label">Phone Numbers</label>
                                <textarea name="phone_numbers" rows="6" class="form-input" placeholder="Enter one number per line&#10;e.g.&#10;8801712345678">{{ old('phone_numbers') }}</textarea>
                                <p class="form-hint">Enter one phone number per line.</p>
                                <x-input-error :messages="$errors->get('phone_numbers')" class="mt-2" />
                            </div>
                            <div x-show="phoneListType === 'csv'" x-transition class="form-group">
                                <label class="form-label">CSV File</label>
                                <input type="file" name="csv_file" accept=".csv,.txt" class="form-input">
                                <p class="form-hint">Upload a CSV with one number per row.</p>
                                <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Call Settings --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Call Settings</h3>
                        <p class="form-card-subtitle">Configure how calls are placed</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Max Concurrent Calls</label>
                                <input type="number" x-ref="maxConcurrent" value="5" class="form-input bg-gray-50" readonly>
                                <input type="hidden" name="max_concurrent" x-ref="maxConcurrentHidden" value="5">
                                <p class="form-hint">Set by SIP account channel limit</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" name="ring_timeout" value="{{ old('ring_timeout', 30) }}" min="10" max="120" class="form-input">
                                <p class="form-hint">How long to ring before giving up.</p>
                                <x-input-error :messages="$errors->get('ring_timeout')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.broadcasts.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" name="action" value="draft" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save Draft
                    </button>
                    <button type="submit" name="action" value="submit" class="btn-primary" style="background: #059669;">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Create Broadcast
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4" style="position:sticky; top:1rem;">
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">How It Works</h3></div>
                    <div class="detail-card-body space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                            <div><p class="text-sm font-medium text-gray-800">Pick a Template</p><p class="text-xs text-gray-500">Search voice or survey template</p></div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                            <div><p class="text-sm font-medium text-gray-800">Client Auto-Fills</p><p class="text-xs text-gray-500">Name, balance & SIP accounts load</p></div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                            <div><p class="text-sm font-medium text-gray-800">Add Phone Numbers</p><p class="text-xs text-gray-500">Enter manually or upload CSV</p></div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                            <div><p class="text-sm font-medium text-gray-800">Start & Monitor</p><p class="text-xs text-gray-500">Launch now or schedule for later</p></div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Billing</h3></div>
                    <div class="detail-card-body text-sm space-y-2">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-gray-600">Calls billed to <strong>client's balance</strong></p>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-gray-600">Auto-pauses when balance is <strong>low</strong></p>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-gray-600">Answered calls only — <strong>no charge</strong> for failed</p>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Best Practices</h3></div>
                    <div class="detail-card-body text-xs text-gray-500 space-y-2">
                        <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span><span>Use <strong>5-10 concurrent</strong> calls</span></div>
                        <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span><span>Schedule during <strong>business hours</strong></span></div>
                        <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span><span>Keep voice messages under <strong>60 seconds</strong></span></div>
                        <div class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span><span><strong>30s ring timeout</strong> works well</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
