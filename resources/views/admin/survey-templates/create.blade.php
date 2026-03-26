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
              surveyQuestions: [{ type: 'question', label: '', max_digits: 1, timeout: 10, max_retries: 2, options: [{digit: '1', label: ''}] }],
              hasIntro: false,
              clientOpen: false,
              clientSearch: '',
              clientId: '',
              clientResults: [],
              clientDebounce: null,
              addQuestion() {
                  this.surveyQuestions.push({ type: 'question', label: '', max_digits: 1, timeout: 10, max_retries: 2, options: [{digit: '', label: ''}] });
              },
              removeQuestion(idx) {
                  if (this.surveyQuestions.filter(q => q.type === 'question').length > 1 || this.surveyQuestions[idx].type === 'intro') {
                      this.surveyQuestions.splice(idx, 1);
                      if (this.surveyQuestions[0]?.type !== 'intro') this.hasIntro = false;
                  }
              },
              toggleIntro() {
                  if (this.hasIntro) {
                      this.surveyQuestions.unshift({ type: 'intro', label: 'Welcome message', options: [] });
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
              selectClient(user) { this.clientSearch = user.name; this.clientId = user.id; this.clientOpen = false; },
              clearClient() { this.clientSearch = ''; this.clientId = ''; this.clientResults = []; }
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
                        <div class="form-group">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Customer Satisfaction Survey">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input" rows="2" placeholder="Optional description">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <div class="relative">
                                <input type="hidden" name="client_id" x-model="clientId">
                                <input type="text" x-model="clientSearch" @input="searchClients(); clientOpen = true" @focus="clientOpen = true" placeholder="Search client..." class="form-input" x-ref="clientInput">
                                <div x-show="clientOpen && clientResults.length > 0" @click.away="clientOpen = false" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    <template x-for="user in clientResults" :key="user.id">
                                        <button type="button" @click="selectClient(user)" class="w-full px-4 py-2 text-left hover:bg-indigo-50 text-sm" x-text="user.name + ' (' + user.email + ')'"></button>
                                    </template>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Questions --}}
                <div class="form-card">
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
                            <div class="border border-gray-200 rounded-lg p-4 space-y-3" :class="q.type === 'intro' ? 'bg-blue-50/50 border-blue-200' : 'bg-white'">
                                <input type="hidden" :name="'survey_questions[' + qIdx + '][type]'" :value="q.type">

                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-800">
                                        <span x-show="q.type === 'intro'" class="text-blue-600">Welcome / Intro</span>
                                        <span x-show="q.type === 'question'">Question <span x-text="surveyQuestions.filter((s, i) => s.type === 'question' && i <= qIdx).length"></span></span>
                                    </h4>
                                    <button type="button" @click="removeQuestion(qIdx)" class="p-1 text-red-400 hover:text-red-600 rounded" x-show="q.type === 'intro' || surveyQuestions.filter(s => s.type === 'question').length > 1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                {{-- Voice File Upload --}}
                                <div class="form-group">
                                    <label class="form-label text-xs">Voice File (WAV/MP3, max 10MB)</label>
                                    <input type="file" :name="'survey_questions[' + qIdx + '][voice_file]'" accept=".wav,.mp3" class="form-input text-sm" required>
                                </div>

                                {{-- Label --}}
                                <div class="form-group">
                                    <label class="form-label text-xs" x-text="q.type === 'intro' ? 'Description' : 'Question Label'"></label>
                                    <input type="text" :name="'survey_questions[' + qIdx + '][label]'" x-model="q.label" class="form-input text-sm" :placeholder="q.type === 'intro' ? 'Welcome message' : 'e.g. How satisfied are you?'">
                                </div>

                                <template x-if="q.type === 'question'">
                                    <div class="space-y-3">
                                        <div class="flex gap-3">
                                            <div class="form-group" style="flex:0 0 100px;"><label class="form-label text-xs">Max Digits</label><input type="number" :name="'survey_questions[' + qIdx + '][max_digits]'" x-model="q.max_digits" min="1" max="10" class="form-input text-sm"></div>
                                            <div class="form-group" style="flex:0 0 100px;"><label class="form-label text-xs">Timeout (s)</label><input type="number" :name="'survey_questions[' + qIdx + '][timeout]'" x-model="q.timeout" min="3" max="60" class="form-input text-sm"></div>
                                            <div class="form-group" style="flex:0 0 100px;"><label class="form-label text-xs">Retries</label><input type="number" :name="'survey_questions[' + qIdx + '][max_retries]'" x-model="q.max_retries" min="0" max="5" class="form-input text-sm"></div>
                                        </div>
                                        <div>
                                            <label class="form-label text-xs">Response Options</label>
                                            <div class="space-y-1.5">
                                                <template x-for="(opt, oIdx) in q.options" :key="oIdx">
                                                    <div class="flex items-center gap-2">
                                                        <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][digit]'" x-model="opt.digit" class="form-input text-sm" style="max-width:60px;" placeholder="#">
                                                        <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][label]'" x-model="opt.label" class="form-input text-sm" placeholder="Label">
                                                        <button type="button" @click="removeOption(qIdx, oIdx)" class="p-1 text-red-400 hover:text-red-600 rounded" x-show="q.options.length > 1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                                    </div>
                                                </template>
                                            </div>
                                            <button type="button" @click="addOption(qIdx)" class="mt-1.5 text-xs text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                Add Option
                                            </button>
                                        </div>
                                    </div>
                                </template>
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
            <div>
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Instructions</h3></div>
                    <div class="detail-card-body text-sm text-gray-600 space-y-2">
                        <p>1. Upload a voice file for each question</p>
                        <p>2. Define response options (digit + label)</p>
                        <p>3. Template will be sent for approval</p>
                        <p>4. Once approved, use in broadcasts</p>
                        <p class="text-xs text-gray-400 pt-2">Supported formats: WAV, MP3 (max 10MB)</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
