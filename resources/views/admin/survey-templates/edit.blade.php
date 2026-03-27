<x-admin-layout>
    <x-slot name="header">Edit Survey Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit: {{ $template->name }}</h2>
            <p class="page-subtitle">Modify survey template questions and settings</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.survey-templates.show', $template) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Cancel
            </a>
        </div>
    </div>

    @php
        $config = $template->config ?? ['version' => 2, 'questions' => []];
        $existingQuestions = collect($config['questions'] ?? [])->map(function ($q) {
            return [
                'type' => $q['type'] ?? 'question',
                'label' => $q['label'] ?? '',
                'max_digits' => $q['max_digits'] ?? 1,
                'timeout' => $q['timeout'] ?? 10,
                'max_retries' => $q['max_retries'] ?? 2,
                'options' => collect($q['options'] ?? [])->map(fn($label, $digit) => ['digit' => (string)$digit, 'label' => $label])->values()->toArray(),
                'voice_file_id' => $q['voice_file_id'] ?? '',
                'voice_file_name' => !empty($q['voice_file_id']) ? (\App\Models\VoiceFile::find($q['voice_file_id'])?->name ?? '') : '',
                'voice_file_duration' => !empty($q['voice_file_id']) ? (\App\Models\VoiceFile::find($q['voice_file_id'])?->duration ?? '') : '',
                'uploading' => false,
                'uploadError' => '',
            ];
        })->toArray();
        $hasIntroExisting = collect($config['questions'] ?? [])->contains('type', 'intro');
    @endphp

    <form method="POST" action="{{ route('admin.survey-templates.update', $template) }}" enctype="multipart/form-data"
          x-data="{
              surveyQuestions: {{ json_encode($existingQuestions) }},
              hasIntro: {{ $hasIntroExisting ? 'true' : 'false' }},
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
                  if (this.hasIntro) this.surveyQuestions.unshift({ type: 'intro', label: 'Welcome message', options: [], voice_file_id: '', voice_file_name: '', voice_file_duration: '', uploading: false, uploadError: '' });
                  else if (this.surveyQuestions[0]?.type === 'intro') this.surveyQuestions.shift();
              },
              addOption(qIdx) { this.surveyQuestions[qIdx].options.push({ digit: '', label: '' }); },
              removeOption(qIdx, oIdx) { if (this.surveyQuestions[qIdx].options.length > 1) this.surveyQuestions[qIdx].options.splice(oIdx, 1); }
          }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Template Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <input type="text" class="form-input bg-gray-50" value="{{ $template->client?->name ?? '-' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="form-input" placeholder="e.g. Customer Satisfaction Survey">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input" rows="2" placeholder="Optional description">{{ old('description', $template->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Questions --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="form-card-title">Survey Questions</h3>
                                <p class="form-card-subtitle">Edit voice files and configure questions</p>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="hasIntro" @change="toggleIntro()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs font-medium text-gray-600">Welcome Intro</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-card-body space-y-4">
                        <template x-for="(q, qIdx) in surveyQuestions" :key="qIdx">
                            <div class="border border-gray-200 rounded-lg overflow-hidden" :class="q.type === 'intro' ? 'border-blue-200' : ''">
                                <input type="hidden" :name="'survey_questions[' + qIdx + '][type]'" :value="q.type">

                                <div class="px-4 py-2.5 flex items-center justify-between" :class="q.type === 'intro' ? 'bg-blue-50 border-b border-blue-100' : 'bg-gray-50 border-b border-gray-100'">
                                    <h4 class="text-sm font-semibold" :class="q.type === 'intro' ? 'text-blue-700' : 'text-gray-800'">
                                        <span x-show="q.type === 'intro'">Welcome / Intro</span>
                                        <span x-show="q.type === 'question'" x-text="'Question ' + surveyQuestions.filter((s, i) => s.type === 'question' && i <= qIdx).length"></span>
                                    </h4>
                                    <button type="button" @click="removeQuestion(qIdx)" class="p-1 text-gray-300 hover:text-red-500 rounded transition-colors" x-show="q.type === 'intro' || surveyQuestions.filter(s => s.type === 'question').length > 1" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                <div class="p-4 space-y-3" :class="q.type === 'intro' ? 'bg-blue-50/30' : ''">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div class="form-group">
                                            <label class="form-label" x-text="q.type === 'intro' ? 'Description' : 'Question Label'"></label>
                                            <input type="text" :name="'survey_questions[' + qIdx + '][label]'" x-model="q.label" class="form-input" :placeholder="q.type === 'intro' ? 'Welcome to our survey' : 'e.g. How satisfied are you?'">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Voice File</label>
                                            <input type="hidden" :name="'survey_questions[' + qIdx + '][voice_file_id]'" :value="q.voice_file_id || ''">
                                            <div x-show="!q.voice_file_id && !q.uploading">
                                                <label class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 transition-all">
                                                    <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span class="text-sm text-gray-500">Upload audio file <span class="text-xs text-gray-400">(WAV/MP3)</span></span>
                                                    <input type="file" accept=".wav,.mp3" class="hidden"
                                                           @change="let file=$event.target.files[0]; if(!file) return; q.uploading=true; q.uploadError=''; let fd=new FormData(); fd.append('voice_file',file); fd.append('label',q.label||'Survey Audio'); fd.append('_token','{{ csrf_token() }}'); fetch('{{ route('admin.survey-templates.upload-voice-file') }}',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>{if(!r.ok) throw new Error(); return r.json();}).then(d=>{q.voice_file_id=d.id;q.voice_file_name=d.name;q.voice_file_duration=d.duration;q.uploading=false;}).catch(()=>{q.uploadError='Upload failed.';q.uploading=false;});">
                                                </label>
                                                <p x-show="q.uploadError" class="text-xs text-red-500 mt-1" x-text="q.uploadError"></p>
                                            </div>
                                            <div x-show="q.uploading" class="flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg">
                                                <svg class="w-4 h-4 text-indigo-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <span class="text-sm text-indigo-600">Uploading...</span>
                                            </div>
                                            <div x-show="q.voice_file_id" class="flex items-center justify-between px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span class="text-sm font-medium text-emerald-700" x-text="q.voice_file_name || 'Uploaded'"></span>
                                                    <span class="text-xs text-emerald-500" x-text="q.voice_file_duration ? '(' + q.voice_file_duration + 's)' : ''"></span>
                                                </div>
                                                <button type="button" @click="q.voice_file_id='';q.voice_file_name='';q.voice_file_duration='';" class="text-xs text-red-500 hover:text-red-700 font-medium">Change</button>
                                            </div>
                                        </div>
                                    </div>

                                    <template x-if="q.type === 'question'">
                                        <div class="space-y-3">
                                            <div class="flex items-end gap-3">
                                                <div class="form-group" style="width:100px;"><label class="form-label">Max Digits</label><input type="number" :name="'survey_questions[' + qIdx + '][max_digits]'" x-model="q.max_digits" min="1" max="10" class="form-input"></div>
                                                <div class="form-group" style="width:100px;"><label class="form-label">Timeout (s)</label><input type="number" :name="'survey_questions[' + qIdx + '][timeout]'" x-model="q.timeout" min="3" max="60" class="form-input"></div>
                                                <div class="form-group" style="width:100px;"><label class="form-label">Retries</label><input type="number" :name="'survey_questions[' + qIdx + '][max_retries]'" x-model="q.max_retries" min="0" max="5" class="form-input"></div>
                                            </div>
                                            <div>
                                                <label class="form-label">Response Options</label>
                                                <div class="space-y-2">
                                                    <template x-for="(opt, oIdx) in q.options" :key="oIdx">
                                                        <div class="flex items-center gap-2">
                                                            <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][digit]'" x-model="opt.digit" class="form-input text-center font-semibold" style="width:55px; flex:none;" placeholder="#">
                                                            <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][label]'" x-model="opt.label" class="form-input flex-1" placeholder="Option label">
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
                    <button type="submit" class="btn-action-primary-admin">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Update Template
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4" style="position:sticky; top:1rem;">
                {{-- Client --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Client</h3></div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($template->client?->name ?? '?', 0, 2)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $template->client?->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $template->client?->email }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-xs text-gray-500">Balance</span>
                            <span class="text-sm font-mono font-semibold {{ ($template->client?->balance ?? 0) > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ currency_symbol() }}{{ number_format($template->client?->balance ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Template Info --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Template Info</h3></div>
                    <div class="detail-card-body space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $template->isApproved() ? 'bg-emerald-100 text-emerald-700' : ($template->isPending() ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700') }}">{{ ucfirst($template->status) }}</span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">Questions</span><span class="font-medium text-gray-700">{{ $template->getQuestionCount() }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Has Intro</span><span class="font-medium text-gray-700">{{ collect($template->config['questions'] ?? [])->contains('type', 'intro') ? 'Yes' : 'No' }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="font-medium text-gray-700">{{ $template->created_at->format('d M Y, g:i A') }}</span></div>
                        @if($template->approved_at)
                            <div class="flex justify-between"><span class="text-gray-500">Approved</span><span class="font-medium text-gray-700">{{ $template->approved_at->format('d M Y') }}</span></div>
                        @endif
                        @if($template->rejection_reason)
                            <div class="mt-2 p-2 bg-red-50 rounded-lg">
                                <p class="text-xs text-red-600 font-medium">Rejection Reason:</p>
                                <p class="text-xs text-red-500 mt-0.5">{{ $template->rejection_reason }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- What You Can Change --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">What You Can Change</h3></div>
                    <div class="detail-card-body text-xs space-y-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-gray-600">Template Name & Description</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-gray-600">Questions & Voice Files</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-gray-600">Response Options & Timeouts</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-gray-600">Add/Remove Questions & Intro</span>
                        </div>
                        <div class="border-t border-gray-100 pt-2 mt-2">
                            <div class="flex items-center gap-2 text-gray-400">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Client — fixed</span>
                            </div>
                        </div>
                        <p class="text-gray-400 pt-1">Editing will not change the approval status.</p>
                    </div>
                </div>

                {{-- Voice File Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Voice File Tips</h3></div>
                    <div class="detail-card-body text-xs text-gray-500 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></span>
                            <span>Each question needs its own voice file</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></span>
                            <span>Click <strong>"Change"</strong> to replace a voice file</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></span>
                            <span>Files auto-convert to 8kHz WAV</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0"></span>
                            <span>Keep each audio under <strong>30 seconds</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
