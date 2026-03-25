<x-admin-layout>
    <x-slot name="header">Create Broadcast</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Create Broadcast</h2>
                <p class="page-subtitle">Set up a new voice broadcast campaign for a client</p>
            </div>
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
              surveyOptions: {{ json_encode(old('survey_options', [['digit' => '1', 'label' => '']])) }},
              clientOpen: false,
              clientSearch: '{{ old('client_id_name', '') }}',
              clientId: '{{ old('client_id', '') }}',
              clientResults: [],
              clientLoading: false,
              clientDebounce: null,
              sipAccounts: [],
              voiceFiles: [],
              addSurveyOption() {
                  this.surveyOptions.push({ digit: '', label: '' });
              },
              removeSurveyOption(index) {
                  if (this.surveyOptions.length > 1) {
                      this.surveyOptions.splice(index, 1);
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
              selectClient(user) {
                  this.clientSearch = user.name;
                  this.clientId = user.id;
                  this.clientOpen = false;
                  this.loadClientData(user.id);
              },
              clearClient() {
                  this.clientSearch = '';
                  this.clientId = '';
                  this.clientResults = [];
                  this.sipAccounts = [];
                  this.voiceFiles = [];
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
                            {{-- Client Selection --}}
                            <div class="form-group">
                                <label class="form-label">Client</label>
                                <div class="relative">
                                    <input type="hidden" name="client_id" :value="clientId">
                                    <div class="relative">
                                        <input type="text"
                                               x-ref="clientInput"
                                               x-model="clientSearch"
                                               @input="clientOpen = true; searchClients()"
                                               @focus="clientOpen = true; searchClients()"
                                               @click.away="clientOpen = false"
                                               class="form-input"
                                               placeholder="Search client by name or email..."
                                               autocomplete="off">
                                        <button type="button" x-show="clientId" @click="clearClient()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="clientOpen && clientResults.length > 0" x-transition
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        <template x-for="user in clientResults" :key="user.id">
                                            <div @click="selectClient(user)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer">
                                                <div class="text-sm font-medium text-gray-900" x-text="user.name"></div>
                                                <div class="text-xs text-gray-500" x-text="user.email"></div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="clientOpen && clientLoading" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center">
                                        <span class="text-sm text-gray-500">Searching...</span>
                                    </div>
                                </div>
                                <p class="form-hint">Select the client this broadcast belongs to.</p>
                                <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                            </div>

                            {{-- Name --}}
                            <div class="form-group">
                                <label for="name" class="form-label">Broadcast Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. March Promo Campaign">
                                <p class="form-hint">A descriptive name to identify this broadcast.</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            {{-- SIP Account --}}
                            <div class="form-group">
                                <label for="sip_account_id" class="form-label">SIP Account</label>
                                <select id="sip_account_id" name="sip_account_id" required class="form-input">
                                    <option value="">Select SIP Account</option>
                                    <template x-for="sip in sipAccounts" :key="sip.id">
                                        <option :value="sip.id" x-text="sip.username + (sip.caller_id_number ? ' (' + sip.caller_id_number + ')' : '')"></option>
                                    </template>
                                </select>
                                <p class="form-hint">Populated after selecting a client.</p>
                                <x-input-error :messages="$errors->get('sip_account_id')" class="mt-2" />
                            </div>

                            {{-- Voice File --}}
                            <div class="form-group">
                                <label for="voice_file_id" class="form-label">Voice File</label>
                                <select id="voice_file_id" name="voice_file_id" required class="form-input">
                                    <option value="">Select Voice File</option>
                                    <template x-for="file in voiceFiles" :key="file.id">
                                        <option :value="file.id" x-text="file.name + ' (' + file.format.toUpperCase() + ')'"></option>
                                    </template>
                                </select>
                                <p class="form-hint">Only approved voice files are shown.</p>
                                <x-input-error :messages="$errors->get('voice_file_id')" class="mt-2" />
                            </div>

                            {{-- Broadcast Type --}}
                            <div class="form-group">
                                <label class="form-label">Broadcast Type</label>
                                <div class="flex gap-4 mt-1">
                                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                           :class="type === 'simple' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                        <input type="radio" name="type" value="simple" x-model="type" class="sr-only">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                        </svg>
                                        <span class="font-medium text-sm">Simple</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors"
                                           :class="type === 'survey' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                        <input type="radio" name="type" value="survey" x-model="type" class="sr-only">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                        </svg>
                                        <span class="font-medium text-sm">Survey</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>

                            {{-- Survey Config --}}
                            <div x-show="type === 'survey'" x-transition class="space-y-4">
                                <div class="form-group">
                                    <label for="max_digits" class="form-label">Max Digits</label>
                                    <input type="number" id="max_digits" name="max_digits" value="{{ old('max_digits', 1) }}" min="1" max="10" class="form-input" style="max-width: 120px;">
                                    <p class="form-hint">Maximum number of digits the caller can press.</p>
                                    <x-input-error :messages="$errors->get('max_digits')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="survey_timeout" class="form-label">Response Timeout (seconds)</label>
                                    <input type="number" id="survey_timeout" name="survey_timeout" value="{{ old('survey_timeout', 10) }}" min="3" max="60" class="form-input" style="max-width: 120px;">
                                    <p class="form-hint">How long to wait for a response before moving on.</p>
                                    <x-input-error :messages="$errors->get('survey_timeout')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Survey Options</label>
                                    <div class="space-y-2">
                                        <template x-for="(option, index) in surveyOptions" :key="index">
                                            <div class="flex items-center gap-2">
                                                <input type="text" :name="'survey_options[' + index + '][digit]'" x-model="option.digit" class="form-input" style="max-width: 80px;" placeholder="Digit">
                                                <input type="text" :name="'survey_options[' + index + '][label]'" x-model="option.label" class="form-input" placeholder="e.g. Press 1 for Yes">
                                                <button type="button" @click="removeSurveyOption(index)" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" x-show="surveyOptions.length > 1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" @click="addSurveyOption()" class="mt-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        Add Option
                                    </button>
                                    <x-input-error :messages="$errors->get('survey_options')" class="mt-2" />
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

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.broadcasts.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Broadcast
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
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
