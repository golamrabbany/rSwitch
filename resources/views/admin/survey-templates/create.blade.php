<x-admin-layout>
    <x-slot name="header">Create Survey Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Create Survey Template</h2>
            <p class="page-subtitle">Build a reusable survey with voice files and questions</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.survey-templates.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.survey-templates.store') }}" enctype="multipart/form-data"
          x-data="{
              surveyQuestions: [{ type: 'question', label: '', max_digits: 1, timeout: 10, max_retries: 2, options: [{digit: '1', label: ''}], voice_file_id: '', voice_file_name: '', voice_file_duration: '', uploading: false, uploadError: '' }],
              hasIntro: false,
              clientOpen: false,
              clientSearch: '',
              clientId: '',
              clientResults: [],
              clientDebounce: null,
              addQuestion() {
                  this.surveyQuestions.push({ type: 'question', label: '', max_digits: 1, timeout: 10, max_retries: 2, options: [{digit: '', label: ''}], voice_file_id: '', voice_file_name: '', voice_file_duration: '', uploading: false, uploadError: '' });
              },
              removeQuestion(idx) {
                  if (this.surveyQuestions.filter(q => q.type === 'question').length > 1 || this.surveyQuestions[idx].type === 'intro') {
                      this.surveyQuestions.splice(idx, 1);
                      if (this.surveyQuestions[0]?.type !== 'intro') this.hasIntro = false;
                  }
              },
              toggleIntro() {
                  if (this.hasIntro) {
                      this.surveyQuestions.unshift({ type: 'intro', label: 'Welcome message', options: [], voice_file_id: '', voice_file_name: '', voice_file_duration: '', uploading: false, uploadError: '' });
                  } else {
                      if (this.surveyQuestions[0]?.type === 'intro') this.surveyQuestions.shift();
                  }
              },
              addOption(qIdx) { this.surveyQuestions[qIdx].options.push({ digit: '', label: '' }); },
              removeOption(qIdx, oIdx) { if (this.surveyQuestions[qIdx].options.length > 1) this.surveyQuestions[qIdx].options.splice(oIdx, 1); },
              searchClients() {
                  clearTimeout(this.clientDebounce);
                  this.clientDebounce = setTimeout(() => {
                      if (!this.clientSearch || this.clientSearch.length < 2) { this.clientResults = []; return; }
                      fetch('{{ route("admin.sip-accounts.search-clients") }}?q=' + encodeURIComponent(this.clientSearch), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                      .then(r => r.json()).then(data => { this.clientResults = data; }).catch(() => {});
                  }, 300);
              },
              kycError: '',
              clientEmail: '', clientBalance: 0,
              selectClient(user) {
                  this.clientSearch = user.name;
                  this.clientId = user.id;
                  this.clientEmail = user.email;
                  this.clientBalance = parseFloat(user.balance || 0);
                  this.clientOpen = false;
                  if (user.kyc_status !== 'approved') {
                      this.kycError = user.name + '\'s KYC is not approved (' + (user.kyc_status || 'not submitted') + '). Template cannot be created.';
                  } else {
                      this.kycError = '';
                  }
              },
              clearClient() { this.clientSearch = ''; this.clientId = ''; this.clientResults = []; this.kycError = ''; this.clientEmail = ''; this.clientBalance = 0; }
          }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Template Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        {{-- Client (first, same as Create SIP Account) --}}
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
                                {{-- Dropdown --}}
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
                                {{-- No results --}}
                                <div x-show="clientOpen && clientSearch.length >= 2 && clientResults.length === 0 && !clientId" x-cloak
                                     @click.outside="clientOpen = false"
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                    No clients found matching "<span x-text="clientSearch"></span>"
                                </div>
                            </div>
                            <p class="form-hint">Select the client this survey template is for</p>

                            {{-- Client Info Banner --}}
                            <div x-show="clientId && !kycError" x-cloak class="mt-2 flex items-center justify-between p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <span class="text-xs font-bold text-indigo-600" x-text="clientSearch.substring(0, 2).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-indigo-800" x-text="clientSearch"></p>
                                        <p class="text-xs text-indigo-600" x-text="clientEmail"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-mono font-semibold" :class="clientBalance > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + clientBalance.toFixed(2)"></p>
                                    <p class="text-xs text-gray-500">Balance</p>
                                </div>
                            </div>

                            <div x-show="kycError" x-cloak class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200">
                                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span class="text-xs text-red-700" x-text="kycError"></span>
                            </div>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>

                        <div class="form-group" :style="kycError ? 'opacity: 0.4; pointer-events: none;' : ''">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Customer Satisfaction Survey">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="form-group" :style="kycError ? 'opacity: 0.4; pointer-events: none;' : ''">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input" rows="2" placeholder="Optional description">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Questions --}}
                <div class="form-card" :style="kycError ? 'opacity: 0.4; pointer-events: none;' : ''">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Survey Questions</h3>
                        <p class="form-card-subtitle">Upload voice files and configure questions</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        {{-- Intro Toggle --}}
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="hasIntro" @change="toggleIntro()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">Add Welcome/Intro</span>
                            </label>
                            <span class="text-xs text-gray-400">(plays first, no DTMF)</span>
                        </div>

                        <template x-for="(q, qIdx) in surveyQuestions" :key="qIdx">
                            <div class="border border-gray-200 rounded-lg overflow-hidden" :class="q.type === 'intro' ? 'border-blue-200' : ''">
                                <input type="hidden" :name="'survey_questions[' + qIdx + '][type]'" :value="q.type">

                                {{-- Question Header --}}
                                <div class="px-4 py-2.5 flex items-center justify-between" :class="q.type === 'intro' ? 'bg-blue-50 border-b border-blue-100' : 'bg-gray-50 border-b border-gray-100'">
                                    <h4 class="text-sm font-semibold" :class="q.type === 'intro' ? 'text-blue-700' : 'text-gray-800'">
                                        <span x-show="q.type === 'intro'">Welcome / Intro <span class="font-normal text-xs text-blue-400">- plays first, no DTMF</span></span>
                                        <span x-show="q.type === 'question'">Question <span x-text="surveyQuestions.filter((s, i) => s.type === 'question' && i <= qIdx).length"></span></span>
                                    </h4>
                                    <button type="button" @click="removeQuestion(qIdx)" class="p-1 text-gray-300 hover:text-red-500 rounded transition-colors" x-show="q.type === 'intro' || surveyQuestions.filter(s => s.type === 'question').length > 1" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                {{-- Question Body --}}
                                <div class="p-4 space-y-3" :class="q.type === 'intro' ? 'bg-blue-50/30' : ''">
                                    {{-- Label + Voice File side by side --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div class="form-group">
                                            <label class="form-label" x-text="q.type === 'intro' ? 'Description' : 'Question Label'"></label>
                                            <input type="text" :name="'survey_questions[' + qIdx + '][label]'" x-model="q.label" class="form-input" :placeholder="q.type === 'intro' ? 'Welcome to our survey' : 'e.g. How satisfied are you?'">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Voice File</label>
                                            <input type="hidden" :name="'survey_questions[' + qIdx + '][voice_file_id]'" :value="q.voice_file_id || ''">

                                            {{-- Upload Area --}}
                                            <div x-show="!q.voice_file_id && !q.uploading">
                                                <label class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 transition-all">
                                                    <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span class="text-sm text-gray-500">Upload audio file <span class="text-xs text-gray-400">(WAV/MP3)</span></span>
                                                    <input type="file" accept=".wav,.mp3" class="hidden"
                                                           @change="
                                                               let file = $event.target.files[0];
                                                               if (!file) return;
                                                               q.uploading = true;
                                                               q.uploadError = '';
                                                               let fd = new FormData();
                                                               fd.append('voice_file', file);
                                                               fd.append('label', q.label || 'Survey Audio');
                                                               fd.append('_token', '{{ csrf_token() }}');
                                                               fetch('{{ route('admin.survey-templates.upload-voice-file') }}', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                                               .then(r => { if (!r.ok) throw new Error('Upload failed'); return r.json(); })
                                                               .then(data => { q.voice_file_id = data.id; q.voice_file_name = data.name; q.voice_file_duration = data.duration; q.uploading = false; })
                                                               .catch(e => { q.uploadError = 'Upload failed. Check file format.'; q.uploading = false; });
                                                           ">
                                                </label>
                                                <p x-show="q.uploadError" class="text-xs text-red-500 mt-1" x-text="q.uploadError"></p>
                                            </div>

                                            {{-- Uploading State --}}
                                            <div x-show="q.uploading" class="flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg">
                                                <svg class="w-4 h-4 text-indigo-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <span class="text-sm text-indigo-600">Uploading & converting...</span>
                                            </div>

                                            {{-- Uploaded State --}}
                                            <div x-show="q.voice_file_id" class="flex items-center justify-between px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span class="text-sm font-medium text-emerald-700" x-text="q.voice_file_name || 'Uploaded'"></span>
                                                    <span class="text-xs text-emerald-500" x-text="q.voice_file_duration ? '(' + q.voice_file_duration + 's)' : ''"></span>
                                                </div>
                                                <button type="button" @click="q.voice_file_id = ''; q.voice_file_name = ''; q.voice_file_duration = '';" class="text-xs text-red-500 hover:text-red-700 font-medium">Remove</button>
                                            </div>
                                        </div>
                                    </div>

                                    <template x-if="q.type === 'question'">
                                        <div class="space-y-3">
                                            {{-- Settings row --}}
                                            <div class="flex items-end gap-3">
                                                <div class="form-group" style="width:100px;">
                                                    <label class="form-label">Max Digits</label>
                                                    <input type="number" :name="'survey_questions[' + qIdx + '][max_digits]'" x-model="q.max_digits" min="1" max="10" class="form-input">
                                                </div>
                                                <div class="form-group" style="width:100px;">
                                                    <label class="form-label">Timeout (s)</label>
                                                    <input type="number" :name="'survey_questions[' + qIdx + '][timeout]'" x-model="q.timeout" min="3" max="60" class="form-input">
                                                </div>
                                                <div class="form-group" style="width:100px;">
                                                    <label class="form-label">Retries</label>
                                                    <input type="number" :name="'survey_questions[' + qIdx + '][max_retries]'" x-model="q.max_retries" min="0" max="5" class="form-input">
                                                </div>
                                            </div>

                                            {{-- Response Options --}}
                                            <div>
                                                <label class="form-label">Response Options</label>
                                                <div class="space-y-2">
                                                    <template x-for="(opt, oIdx) in q.options" :key="oIdx">
                                                        <div class="flex items-center gap-2">
                                                            <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][digit]'" x-model="opt.digit" class="form-input text-center font-semibold" style="width:55px; flex:none;" placeholder="#">
                                                            <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][label]'" x-model="opt.label" class="form-input flex-1" placeholder="Option label (e.g. Satisfied)">
                                                            <button type="button" @click="removeOption(qIdx, oIdx)" class="p-1 text-gray-300 hover:text-red-500 rounded transition-colors" x-show="q.options.length > 1">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                                <button type="button" @click="addOption(qIdx)" class="mt-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                    Add Option
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="addQuestion()" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Add Question
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-action-primary-admin">Create Template</button>
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
                            <p>Select client & name the template</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                            <p>Upload voice file for each question</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                            <p>Define DTMF options (digit + label)</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                            <p>Submit for Super Admin approval</p>
                        </div>
                    </div>
                </div>

                {{-- File Requirements --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Voice File Requirements</h3></div>
                    <div class="detail-card-body text-sm text-gray-500 space-y-2">
                        <div class="flex items-center justify-between">
                            <span>Formats</span>
                            <span class="font-medium text-gray-700">WAV, MP3</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Max size</span>
                            <span class="font-medium text-gray-700">10MB per file</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Conversion</span>
                            <span class="font-medium text-gray-700">Auto 8kHz WAV</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Upload</span>
                            <span class="font-medium text-gray-700">Instant per question</span>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Tips</h3></div>
                    <div class="detail-card-body text-xs text-gray-500 space-y-2">
                        <p>Record clear, slow-paced audio for better response rates.</p>
                        <p>Keep each question under 30 seconds.</p>
                        <p>Use the Welcome Intro for greeting and instructions.</p>
                        <p>Limit to 3-5 questions per survey for best completion rates.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
