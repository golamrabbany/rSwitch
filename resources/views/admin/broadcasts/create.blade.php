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
              selectedTemplateId: '{{ old('voice_file_id', '') }}',
              selectedSurveyId: '{{ old('survey_template_id', '') }}',
              clientName: '',
              clientEmail: '',
              sipAccounts: [],
              loading: false,
              loadTemplateData() {
                  let type = this.type === 'simple' ? 'voice' : 'survey';
                  let templateId = this.type === 'simple' ? this.selectedTemplateId : this.selectedSurveyId;
                  if (!templateId) { this.clientName = ''; this.sipAccounts = []; return; }
                  this.loading = true;
                  fetch('{{ route('admin.broadcasts.template-data') }}?type=' + type + '&template_id=' + templateId, {
                      headers: { 'X-Requested-With': 'XMLHttpRequest' }
                  })
                  .then(r => r.json())
                  .then(data => {
                      this.clientName = data.client.name;
                      this.clientEmail = data.client.email;
                      this.sipAccounts = data.sip_accounts || [];
                      this.loading = false;
                  })
                  .catch(() => { this.loading = false; });
              }
          }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Details</h3>
                        <p class="form-card-subtitle">Select a template — client and SIP accounts auto-fill</p>
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
                                            <input type="radio" name="type" value="simple" x-model="type" @change="clientName = ''; sipAccounts = []; selectedSurveyId = ''" class="sr-only">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                            <span class="font-medium text-sm">Simple</span>
                                        </label>
                                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                               :class="type === 'survey' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                            <input type="radio" name="type" value="survey" x-model="type" @change="clientName = ''; sipAccounts = []; selectedTemplateId = ''" class="sr-only">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                            <span class="font-medium text-sm">Survey</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Voice Template (Simple) --}}
                            <div class="form-group" x-show="type === 'simple'" x-transition>
                                <label class="form-label">Voice Template</label>
                                <select name="voice_file_id" x-model="selectedTemplateId" @change="loadTemplateData()" class="form-input">
                                    <option value="">Select Voice Template</option>
                                    @foreach($voiceTemplates as $vt)
                                        <option value="{{ $vt->id }}">{{ $vt->name }} — {{ $vt->user->name ?? 'Unknown' }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Approved voice templates with client name</p>
                                <x-input-error :messages="$errors->get('voice_file_id')" class="mt-2" />
                            </div>

                            {{-- Survey Template (Survey) --}}
                            <div class="form-group" x-show="type === 'survey'" x-transition>
                                <label class="form-label">Survey Template</label>
                                <select name="survey_template_id" x-model="selectedSurveyId" @change="loadTemplateData()" class="form-input">
                                    <option value="">Select Survey Template</option>
                                    @foreach($surveyTemplates as $st)
                                        <option value="{{ $st->id }}">{{ $st->name }} — {{ $st->client->name ?? 'Unknown' }} ({{ $st->getQuestionCount() }} questions)</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Approved survey templates with client name</p>
                                <x-input-error :messages="$errors->get('survey_template_id')" class="mt-2" />
                            </div>

                            {{-- Auto-filled Client Info --}}
                            <div x-show="clientName" x-transition class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-indigo-800" x-text="'Client: ' + clientName"></p>
                                    <p class="text-xs text-indigo-600" x-text="clientEmail"></p>
                                </div>
                            </div>

                            {{-- SIP Account --}}
                            <div class="form-group">
                                <label class="form-label">SIP Account</label>
                                <select name="sip_account_id" required class="form-input">
                                    <option value="">Select SIP Account</option>
                                    <template x-for="sip in sipAccounts" :key="sip.id">
                                        <option :value="sip.id" x-text="sip.username"></option>
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
                                <input type="number" name="max_concurrent" value="{{ old('max_concurrent', 5) }}" min="1" max="50" class="form-input">
                                <p class="form-hint">Maximum simultaneous calls.</p>
                                <x-input-error :messages="$errors->get('max_concurrent')" class="mt-2" />
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
                    <div class="detail-card-header"><h3 class="detail-card-title">How It Works</h3></div>
                    <div class="detail-card-body text-sm text-gray-600 space-y-3">
                        <div class="flex items-start gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                            <p>Select type & pick a template</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                            <p>Client auto-fills from template</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                            <p>Add phone numbers & schedule</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                            <p>Start broadcast & monitor results</p>
                        </div>
                    </div>
                </div>

                {{-- Requirements --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Requirements</h3></div>
                    <div class="detail-card-body text-sm text-gray-500 space-y-2">
                        <div class="flex items-center justify-between">
                            <span>Template</span>
                            <span class="font-medium text-gray-700">Approved</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>SIP Account</span>
                            <span class="font-medium text-gray-700">Active</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Client Balance</span>
                            <span class="font-medium text-gray-700">Sufficient</span>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Tips</h3></div>
                    <div class="detail-card-body text-xs text-gray-500 space-y-2">
                        <p>Calls are billed to the client's prepaid balance.</p>
                        <p>Broadcast auto-pauses if balance runs low.</p>
                        <p>Use 5-10 concurrent calls for best results.</p>
                        <p>Schedule broadcasts during business hours for higher answer rates.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
