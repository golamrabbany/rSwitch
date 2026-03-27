<x-admin-layout>
    <x-slot name="header">Upload Voice Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Upload Voice Template</h2>
            <p class="page-subtitle">Upload a voice template on behalf of a client</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.voice-files.store') }}" enctype="multipart/form-data"
                  x-data="{
                      clientOpen: false, clientSearch: '', clientId: '{{ old('user_id') }}', clientResults: [], clientDebounce: null, kycError: '',
                      voiceFileId: '', voiceFileName: '', voiceFileDuration: '', uploading: false, uploadError: '',
                      searchClients() {
                          clearTimeout(this.clientDebounce);
                          this.clientDebounce = setTimeout(() => {
                              if (!this.clientSearch || this.clientSearch.length < 2) { this.clientResults = []; return; }
                              fetch('{{ route('admin.sip-accounts.search-clients') }}?q=' + encodeURIComponent(this.clientSearch), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                              .then(r => r.json()).then(data => { this.clientResults = data; }).catch(() => {});
                          }, 300);
                      },
                      clientEmail: '', clientBalance: 0, clientKyc: '',
                      selectClient(user) {
                          this.clientSearch = user.name;
                          this.clientId = user.id;
                          this.clientEmail = user.email;
                          this.clientBalance = parseFloat(user.balance || 0);
                          this.clientKyc = user.kyc_status || 'none';
                          this.clientOpen = false;
                          if (user.kyc_status !== 'approved') {
                              this.kycError = user.name + '\'s KYC is not approved (' + (user.kyc_status || 'not submitted') + ').';
                          } else {
                              this.kycError = '';
                          }
                      },
                      clearClient() { this.clientSearch = ''; this.clientId = ''; this.clientResults = []; this.kycError = ''; this.clientEmail = ''; this.clientBalance = 0; this.clientKyc = ''; }
                  }">
                @csrf

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Voice Template Details</h3>
                        <p class="form-card-subtitle">Select client and upload audio file</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        {{-- Client Search (same as SIP Account / Survey Template) --}}
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <div class="relative">
                                <input type="hidden" name="user_id" :value="clientId">
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
                                <div x-show="clientOpen && clientSearch.length >= 2 && clientResults.length === 0 && !clientId" x-cloak
                                     @click.outside="clientOpen = false"
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                    No clients found matching "<span x-text="clientSearch"></span>"
                                </div>
                            </div>
                            <p class="form-hint">The client who will own this voice template</p>

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
                                    <p class="text-xs text-emerald-500">KYC Approved</p>
                                </div>
                            </div>

                            <div x-show="kycError" x-cloak class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200">
                                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span class="text-xs text-red-700" x-text="kycError"></span>
                            </div>
                            @error('user_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group" :style="kycError ? 'opacity: 0.4; pointer-events: none;' : ''">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Welcome Message, Satisfaction Q1" x-ref="nameInput">
                            <p class="form-hint">A descriptive name for this voice template</p>
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group" :style="kycError ? 'opacity: 0.4; pointer-events: none;' : ''">
                            <label class="form-label">Audio File</label>
                            <input type="hidden" name="voice_file_id" :value="voiceFileId">

                            {{-- Upload Area --}}
                            <div x-show="!voiceFileId && !uploading">
                                <label class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 transition-all">
                                    <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-sm text-gray-500">Upload audio file <span class="text-xs text-gray-400">(WAV/MP3, max 10MB)</span></span>
                                    <input type="file" accept=".wav,.mp3" class="hidden"
                                           @change="
                                               let file = $event.target.files[0];
                                               if (!file) return;
                                               uploading = true;
                                               uploadError = '';
                                               let fd = new FormData();
                                               fd.append('voice_file', file);
                                               fd.append('label', $refs.nameInput?.value || 'Voice Template');
                                               fd.append('_token', '{{ csrf_token() }}');
                                               fetch('{{ route('admin.survey-templates.upload-voice-file') }}', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                               .then(r => { if (!r.ok) throw new Error('Upload failed'); return r.json(); })
                                               .then(data => { voiceFileId = data.id; voiceFileName = data.name; voiceFileDuration = data.duration; uploading = false; })
                                               .catch(e => { uploadError = 'Upload failed. Check file format and size.'; uploading = false; });
                                           ">
                                </label>
                                <p x-show="uploadError" class="text-xs text-red-500 mt-1" x-text="uploadError"></p>
                            </div>

                            {{-- Uploading --}}
                            <div x-show="uploading" class="flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg">
                                <svg class="w-4 h-4 text-indigo-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span class="text-sm text-indigo-600">Uploading & converting...</span>
                            </div>

                            {{-- Uploaded --}}
                            <div x-show="voiceFileId" class="flex items-center justify-between px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span class="text-sm font-medium text-emerald-700" x-text="voiceFileName || 'Uploaded'"></span>
                                    <span class="text-xs text-emerald-500" x-text="voiceFileDuration ? '(' + voiceFileDuration + 's)' : ''"></span>
                                </div>
                                <button type="button" @click="voiceFileId = ''; voiceFileName = ''; voiceFileDuration = '';" class="text-xs text-red-500 hover:text-red-700 font-medium">Remove</button>
                            </div>

                            @error('voice_file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end pt-2" :style="kycError ? 'opacity: 0.4; pointer-events: none;' : ''">
                            <button type="submit" class="btn-action-primary-admin">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Upload Template
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4" style="position:sticky; top:1rem;">
            @if(auth()->user()->isSuperAdmin())
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-sm text-blue-800 font-medium">Auto-Approved</p>
                            <p class="text-xs text-blue-600 mt-1">Templates uploaded by Super Admin are automatically approved.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">File Requirements</h3></div>
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
                        <span>Recommended</span>
                        <span class="font-medium text-gray-700">Under 5 minutes</span>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Tips</h3></div>
                <div class="detail-card-body text-xs text-gray-500 space-y-2">
                    <p>Record in a quiet environment for best quality.</p>
                    <p>Speak clearly and at a moderate pace.</p>
                    <p>Files are auto-converted to Asterisk-compatible format.</p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
