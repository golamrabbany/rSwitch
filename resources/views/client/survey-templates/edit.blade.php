<x-client-layout>
    <x-slot name="header">Edit Survey Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit Survey Template</h2>
            <p class="page-subtitle">{{ $surveyTemplate->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.survey-templates.show', $surveyTemplate) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    @php
        $existingQuestions = $surveyTemplate->config['questions'] ?? [];
        $jsQuestions = collect($existingQuestions)->map(function ($q) {
            return [
                'type' => $q['type'] ?? 'question',
                'label' => $q['label'] ?? '',
                'max_digits' => $q['max_digits'] ?? 1,
                'timeout' => $q['timeout'] ?? 10,
                'max_retries' => $q['max_retries'] ?? 2,
                'options' => !empty($q['options']) ? collect($q['options'])->map(fn($label, $digit) => ['digit' => (string) $digit, 'label' => $label])->values()->toArray() : [['digit' => '', 'label' => '']],
                'voice_file_id' => $q['voice_file_id'] ?? '',
                'voice_file_name' => isset($q['voice_file_id']) ? (\App\Models\VoiceFile::find($q['voice_file_id'])?->name ?? '') : '',
                'voice_file_duration' => isset($q['voice_file_id']) ? (\App\Models\VoiceFile::find($q['voice_file_id'])?->duration ?? '') : '',
                'uploading' => false,
                'uploadError' => '',
            ];
        })->toArray();
        $hasIntro = !empty($existingQuestions) && ($existingQuestions[0]['type'] ?? '') === 'intro';
    @endphp

    <form method="POST" action="{{ route('client.survey-templates.update', $surveyTemplate) }}" enctype="multipart/form-data"
          x-data="{
              surveyQuestions: {{ json_encode($jsQuestions ?: [['type' => 'question', 'label' => '', 'max_digits' => 1, 'timeout' => 10, 'max_retries' => 2, 'options' => [{'digit': '1', 'label': ''}], 'voice_file_id' => '', 'voice_file_name' => '', 'voice_file_duration' => '', 'uploading' => false, 'uploadError' => '']]) }},
              hasIntro: {{ $hasIntro ? 'true' : 'false' }},
              addQuestion() { this.surveyQuestions.push({ type: 'question', label: '', max_digits: 1, timeout: 10, max_retries: 2, options: [{digit: '', label: ''}], voice_file_id: '', voice_file_name: '', voice_file_duration: '', uploading: false, uploadError: '' }); },
              removeQuestion(idx) { if (this.surveyQuestions.filter(q => q.type === 'question').length > 1 || this.surveyQuestions[idx].type === 'intro') { this.surveyQuestions.splice(idx, 1); if (this.surveyQuestions[0]?.type !== 'intro') this.hasIntro = false; } },
              toggleIntro() { if (this.hasIntro) this.surveyQuestions.unshift({ type: 'intro', label: 'Welcome message', options: [], voice_file_id: '', voice_file_name: '', voice_file_duration: '', uploading: false, uploadError: '' }); else if (this.surveyQuestions[0]?.type === 'intro') this.surveyQuestions.shift(); },
              addOption(qIdx) { this.surveyQuestions[qIdx].options.push({ digit: '', label: '' }); },
              removeOption(qIdx, oIdx) { if (this.surveyQuestions[qIdx].options.length > 1) this.surveyQuestions[qIdx].options.splice(oIdx, 1); }
          }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="name" value="{{ old('name', $surveyTemplate->name) }}" required class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" value="{{ old('description', $surveyTemplate->description) }}" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header">
                        <div class="flex items-center justify-between w-full">
                            <h3 class="form-card-title">Survey Questions</h3>
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
                                    <button type="button" @click="removeQuestion(qIdx)" class="p-1 text-gray-300 hover:text-red-500" x-show="q.type === 'intro' || surveyQuestions.filter(s => s.type === 'question').length > 1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <div class="p-4 space-y-3" :class="q.type === 'intro' ? 'bg-blue-50/30' : ''">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div class="form-group">
                                            <label class="form-label" x-text="q.type === 'intro' ? 'Description' : 'Question Label'"></label>
                                            <input type="text" :name="'survey_questions[' + qIdx + '][label]'" x-model="q.label" class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Voice File</label>
                                            <input type="hidden" :name="'survey_questions[' + qIdx + '][voice_file_id]'" :value="q.voice_file_id || ''">
                                            <div x-show="!q.voice_file_id && !q.uploading">
                                                <label class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 transition-all">
                                                    <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span class="text-sm text-gray-500">Upload audio</span>
                                                    <input type="file" accept=".wav,.mp3" class="hidden"
                                                           @change="let file=$event.target.files[0]; if(!file) return; q.uploading=true; q.uploadError=''; let fd=new FormData(); fd.append('voice_file',file); fd.append('label',q.label||'Survey Audio'); fd.append('_token','{{ csrf_token() }}'); fetch('{{ route('client.survey-templates.upload-voice-file') }}',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>{if(!r.ok) throw new Error(); return r.json();}).then(d=>{q.voice_file_id=d.id;q.voice_file_name=d.name;q.voice_file_duration=d.duration;q.uploading=false;}).catch(()=>{q.uploadError='Upload failed.';q.uploading=false;});">
                                                </label>
                                            </div>
                                            <div x-show="q.uploading" class="flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg">
                                                <svg class="w-4 h-4 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                <span class="text-sm text-indigo-600">Uploading...</span>
                                            </div>
                                            <div x-show="q.voice_file_id" class="flex items-center justify-between px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span class="text-sm font-medium text-emerald-700" x-text="q.voice_file_name || 'Uploaded'"></span>
                                                </div>
                                                <button type="button" @click="q.voice_file_id='';q.voice_file_name='';q.voice_file_duration='';" class="text-xs text-red-500 hover:text-red-700 font-medium">Change</button>
                                            </div>
                                        </div>
                                    </div>
                                    <template x-if="q.type === 'question'">
                                        <div class="space-y-3">
                                            <div class="flex items-end gap-3">
                                                <div class="form-group" style="width:100px;"><label class="form-label">Max Digits</label><input type="number" :name="'survey_questions[' + qIdx + '][max_digits]'" x-model="q.max_digits" min="1" max="10" class="form-input"></div>
                                                <div class="form-group" style="width:100px;"><label class="form-label">Timeout</label><input type="number" :name="'survey_questions[' + qIdx + '][timeout]'" x-model="q.timeout" min="3" max="60" class="form-input"></div>
                                                <div class="form-group" style="width:100px;"><label class="form-label">Retries</label><input type="number" :name="'survey_questions[' + qIdx + '][max_retries]'" x-model="q.max_retries" min="0" max="5" class="form-input"></div>
                                            </div>
                                            <div>
                                                <label class="form-label">Response Options</label>
                                                <div class="space-y-2">
                                                    <template x-for="(opt, oIdx) in q.options" :key="oIdx">
                                                        <div class="flex items-center gap-2">
                                                            <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][digit]'" x-model="opt.digit" class="form-input text-center font-semibold" style="width:55px;" placeholder="#">
                                                            <input type="text" :name="'survey_questions[' + qIdx + '][options][' + oIdx + '][label]'" x-model="opt.label" class="form-input flex-1" placeholder="Option label">
                                                            <button type="button" @click="removeOption(qIdx, oIdx)" class="p-1 text-gray-300 hover:text-red-500" x-show="q.options.length > 1">
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

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('client.survey-templates.show', $surveyTemplate) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" name="action" value="draft" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Save Draft</button>
                    <button type="submit" name="action" value="submit" class="btn-primary">Update & Submit</button>
                </div>
            </div>

            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Status</h3></div>
                    <div class="detail-card-body">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Current Status</span>
                                <span class="font-medium text-gray-900">{{ ucfirst($surveyTemplate->status) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $surveyTemplate->created_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-client-layout>
