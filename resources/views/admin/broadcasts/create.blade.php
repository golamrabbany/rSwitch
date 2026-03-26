<x-admin-layout>
    <x-slot name="header">Create Broadcast</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Create Broadcast</h2>
            <p class="page-subtitle">Set up a new voice broadcast campaign</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.broadcasts.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.broadcasts.store') }}" enctype="multipart/form-data"
          x-data="{
              type: '{{ old('type', 'simple') }}',
              phoneListType: '{{ old('phone_list_type', 'manual') }}',
              scheduleType: '{{ old('schedule_type', 'now') }}',
              surveyQuestions: [{ type: 'question', voice_file_id: '', label: '', max_digits: 1, timeout: 10, max_retries: 2, options: [{digit: '1', label: ''}] }],
              hasIntro: false,
              clientOpen: false,
              clientSearch: '{{ old('client_id_name', '') }}',
              clientId: '{{ old('client_id', '') }}',
              clientResults: [],
              clientLoading: false,
              clientDebounce: null,
              sipAccounts: [],
              voiceFiles: [],
              surveyTemplateId: '',
              surveyTemplates: [],
              useTemplate: false,
              addQuestion() {
                  this.surveyQuestions.push({ type: 'question', voice_file_id: '', label: '', max_digits: 1, timeout: 10, max_retries: 2, options: [{digit: '', label: ''}] });
              },
              removeQuestion(idx) {
                  if (this.surveyQuestions.filter(q => q.type === 'question').length > 1 || this.surveyQuestions[idx].type === 'intro') {
                      this.surveyQuestions.splice(idx, 1);
                      if (this.surveyQuestions[0]?.type !== 'intro') this.hasIntro = false;
                  }
              },
              toggleIntro() {
                  if (this.hasIntro) {
                      this.surveyQuestions.unshift({ type: 'intro', voice_file_id: '', label: 'Welcome message', options: [] });
                  } else {
                      if (this.surveyQuestions[0]?.type === 'intro') this.surveyQuestions.shift();
                  }
              },
              addOption(qIdx) {
                  this.surveyQuestions[qIdx].options.push({ digit: '', label: '' });
              },
              removeOption(qIdx, oIdx) {
                  if (this.surveyQuestions[qIdx].options.length > 1) {
                      this.surveyQuestions[qIdx].options.splice(oIdx, 1);
                  }
              },
              searchClients() {
                  clearTimeout(this.clientDebounce);
                  this.clientDebounce = setTimeout(() => {
                      if (!this.clientSearch || this.clientSearch.length < 2) {
                          this.clientResults = [];
                          return;
                      }
                      this.clientLoading = true;
                      fetch('{{ route('admin.sip-accounts.search-clients') }}?q=' + encodeURIComponent(this.clientSearch), {
                          headers: { 'X-Requested-With': 'XMLHttpRequest' }
                      })
                      .then(r => r.json())
                      .then(data => { this.clientResults = data; this.clientLoading = false; })
                      .catch(() => { this.clientLoading = false; });
                  }, 300);
              },
              kycError: '',
              selectClient(user) {
                  this.clientSearch = user.name;
                  this.clientId = user.id;
                  this.clientOpen = false;
                  if (user.kyc_status !== 'approved') {
                      this.kycError = user.name + '\'s KYC is not approved (' + (user.kyc_status || 'not submitted') + '). Broadcast cannot be created.';
                  } else {
                      this.kycError = '';
                  }
                  this.loadClientData(user.id);
              },
              clearClient() {
                  this.clientSearch = '';
                  this.clientId = '';
                  this.clientResults = [];
                  this.sipAccounts = [];
                  this.voiceFiles = [];
                  this.surveyTemplates = [];
                  this.surveyTemplateId = '';
                  this.useTemplate = false;
                  this.kycError = '';
                  this.$refs.clientInput.focus();
              },
              loadClientData(clientId) {
                  fetch('{{ route('admin.broadcasts.client-data') }}?client_id=' + clientId, {
                      headers: { 'X-Requested-With': 'XMLHttpRequest' }
                  })
                  .then(r => r.json())
                  .then(data => {
                      this.sipAccounts = data.sip_accounts || [];
                      this.voiceFiles = data.voice_files || [];
                      this.surveyTemplates = data.survey_templates || [];
                      this.surveyTemplateId = '';
                      this.useTemplate = false;
                  });
              }
          }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Details</h3>
                        <p class="form-card-subtitle">Configure the broadcast campaign settings</p>
                    </div>
                    <div class="form-card-body">
                        <div class="space-y-4">
                            {{-- Client Selection (same as SIP Account / Voice Template / Survey Templates) --}}
                            <div class="form-group">
                                <label class="form-label">Client</label>
                                <div class="relative">
                                    <input type="hidden" name="client_id" :value="clientId">
                                    <div class="relative">
                                        <input type="text"
                                               x-ref="clientInput"
                                               x-model="clientSearch"
                                               @focus="clientOpen = true"
                                               @click="clientOpen = true"
                                               @input="clientOpen = true; clientId = ''; searchClients()"
                                               @keydown.escape="clientOpen = false"
                                               @keydown.tab="clientOpen = false"
                                               class="form-input pr-16"
                                               placeholder="Search client by name or email..."
                                               autocomplete="off">
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                            <button type="button" x-show="clientSearch" x-cloak @click="clearClient()" class="p-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                    </div>
                                    <div x-show="clientOpen && clientResults.length > 0" x-cloak @click.outside="clientOpen = false"
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                        <template x-for="user in clientResults" :key="user.id">
                                            <div @click="selectClient(user)"
                                                 class="px-4 py-2 cursor-pointer hover:bg-indigo-50 flex items-center justify-between"
                                                 :class="{ 'bg-indigo-50': clientId == user.id }">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center">
                                                        <span class="text-xs font-medium text-sky-600" x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900" x-text="user.name"></p>
                                                        <p class="text-xs text-gray-500" x-text="user.email"></p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-mono font-semibold" :class="parseFloat(user.balance) > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + parseFloat(user.balance || 0).toFixed(2)"></p>
                                                    <p class="text-xs" :class="user.kyc_status === 'approved' ? 'text-emerald-500' : 'text-amber-500'" x-text="user.kyc_status === 'approved' ? 'KYC Approved' : 'KYC: ' + (user.kyc_status || 'none')"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="clientOpen && clientLoading" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">Searching...</div>
                                    <div x-show="clientOpen && !clientLoading && clientSearch.length >= 2 && clientResults.length === 0 && !clientId" x-cloak
                                         @click.outside="clientOpen = false"
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                        No clients found matching "<span x-text="clientSearch"></span>"
                                    </div>
                                </div>
                                <p class="form-hint">Select the client this broadcast belongs to</p>
                                <div x-show="kycError" x-cloak class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200">
                                    <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span class="text-xs text-red-700" x-text="kycError"></span>
                                </div>
                                <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                            </div>

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

                            {{-- SIP + Template Row --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">SIP Account</label>
                                    <select name="sip_account_id" required class="form-input">
                                        <option value="">Select SIP Account</option>
                                        <template x-for="sip in sipAccounts" :key="sip.id">
                                            <option :value="sip.id" x-text="sip.username + (sip.caller_id_number ? ' (' + sip.caller_id_number + ')' : '')"></option>
                                        </template>
                                    </select>
                                    <p class="form-hint">Populated after selecting a client.</p>
                                    <x-input-error :messages="$errors->get('sip_account_id')" class="mt-2" />
                                </div>

                                {{-- Voice Template (Simple) --}}
                                <div class="form-group" x-show="type === 'simple'" x-transition>
                                    <label class="form-label">Voice Template</label>
                                    <select name="voice_file_id" class="form-input">
                                        <option value="">Select Voice Template</option>
                                        <template x-for="file in voiceFiles" :key="file.id">
                                            <option :value="file.id" x-text="file.name + ' (' + file.format.toUpperCase() + ')'"></option>
                                        </template>
                                    </select>
                                    <p class="form-hint">Approved templates only.</p>
                                    <x-input-error :messages="$errors->get('voice_file_id')" class="mt-2" />
                                </div>

                                {{-- Survey Template (Survey) --}}
                                <div class="form-group" x-show="type === 'survey'" x-transition>
                                    <label class="form-label">Survey Template</label>
                                    <select name="survey_template_id" class="form-input" x-model="surveyTemplateId">
                                        <option value="">Select Survey Template</option>
                                        <template x-for="tpl in surveyTemplates" :key="tpl.id">
                                            <option :value="tpl.id" x-text="tpl.name + ' (' + tpl.question_count + ' questions)'"></option>
                                        </template>
                                    </select>
                                    <p class="form-hint">Approved templates only.</p>
                                    <x-input-error :messages="$errors->get('survey_template_id')" class="mt-2" />
                                </div>
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
                                <label for="phone_numbers" class="form-label">Phone Numbers</label>
                                <textarea id="phone_numbers" name="phone_numbers" rows="6" class="form-input" placeholder="Enter one number per line&#10;e.g.&#10;8801712345678&#10;8801798765432">{{ old('phone_numbers') }}</textarea>
                                <p class="form-hint">Enter one phone number per line.</p>
                                <x-input-error :messages="$errors->get('phone_numbers')" class="mt-2" />
                            </div>

                            <div x-show="phoneListType === 'csv'" x-transition class="form-group">
                                <label for="phone_csv" class="form-label">CSV File</label>
                                <input type="file" id="phone_csv" name="phone_csv" accept=".csv,.txt" class="form-input">
                                <p class="form-hint">Upload a CSV file with one phone number per row.</p>
                                <x-input-error :messages="$errors->get('phone_csv')" class="mt-2" />
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
                                <label for="max_concurrent" class="form-label">Max Concurrent Calls</label>
                                <input type="number" id="max_concurrent" name="max_concurrent" value="{{ old('max_concurrent', 5) }}" min="1" max="50" class="form-input">
                                <p class="form-hint">Maximum simultaneous calls.</p>
                                <x-input-error :messages="$errors->get('max_concurrent')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="ring_timeout" class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" id="ring_timeout" name="ring_timeout" value="{{ old('ring_timeout', 30) }}" min="10" max="120" class="form-input">
                                <p class="form-hint">How long to ring before giving up.</p>
                                <x-input-error :messages="$errors->get('ring_timeout')" class="mt-2" />
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
                                <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                       :class="scheduleType === 'now' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <input type="radio" name="schedule_type" value="now" x-model="scheduleType" class="sr-only">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span class="font-medium text-sm">Start Manually</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                       :class="scheduleType === 'scheduled' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <input type="radio" name="schedule_type" value="scheduled" x-model="scheduleType" class="sr-only">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="font-medium text-sm">Schedule for Later</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="scheduleType === 'now'" x-transition class="text-sm text-gray-500">
                            <p>Broadcast will be created as <strong>draft</strong>. You can start it manually from the broadcast detail page.</p>
                        </div>

                        <div x-show="scheduleType === 'scheduled'" x-transition>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" class="form-input" min="{{ now()->format('Y-m-d') }}">
                                    <x-input-error :messages="$errors->get('scheduled_date')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Time</label>
                                    <input type="time" name="scheduled_time" value="{{ old('scheduled_time') }}" class="form-input">
                                    <x-input-error :messages="$errors->get('scheduled_time')" class="mt-2" />
                                </div>
                            </div>
                            <p class="form-hint mt-2">Broadcast will auto-start at the scheduled time. Server timezone: {{ config('app.timezone') }}</p>
                            <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex justify-end">
                    <button type="submit" class="btn-action-primary-admin">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Create Broadcast
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4" style="position:sticky; top:1rem;">
                {{-- How It Works --}}
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
                                <p class="text-sm text-gray-600">Select a client and configure the broadcast</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">2</span>
                                </div>
                                <p class="text-sm text-gray-600">Upload phone numbers and set call parameters</p>
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

                {{-- Broadcast Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Broadcast Tips</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>The client must have an <strong>approved voice file</strong> and active SIP account</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Calls are billed to the <strong>client's balance</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Ensure the client has <strong>sufficient balance</strong> before starting</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
