<x-reseller-layout>
    <x-slot name="header">Upload Voice Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Upload Voice Template</h2>
            <p class="page-subtitle">Upload a voice file for broadcasting</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <script>var _clients = @json($clientsJson);</script>

    <form method="POST" action="{{ route('reseller.voice-files.store') }}" enctype="multipart/form-data"
          x-data="{
              clientOpen: false,
              clientSearch: '',
              clientId: '{{ old('user_id', '') }}',
              clientResults: [],
              selectedClient: null,
              searchClients() {
                  if (!this.clientSearch || this.clientSearch.length < 1) {
                      this.clientResults = _clients.slice(0, 10);
                      return;
                  }
                  var q = this.clientSearch.toLowerCase();
                  this.clientResults = _clients.filter(function(c) {
                      return c.name.toLowerCase().indexOf(q) > -1 || c.email.toLowerCase().indexOf(q) > -1;
                  }).slice(0, 10);
              },
              selectClient(user) {
                  this.clientSearch = user.name;
                  this.clientId = user.id;
                  this.selectedClient = user;
                  this.clientOpen = false;
              },
              clearClient() {
                  this.clientSearch = '';
                  this.clientId = '';
                  this.clientResults = [];
                  this.selectedClient = null;
                  this.$nextTick(() => this.$refs.clientInput.focus());
              },
              submitForm(e) {
                  if (!this.clientId) {
                      e.preventDefault();
                      this.$refs.clientInput.focus();
                      this.clientOpen = true;
                      this.searchClients();
                  }
              }
          }"
          @submit="submitForm($event)">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Voice Template Details</h3>
                        <p class="form-card-subtitle">Upload a WAV or MP3 file (max 10MB)</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        {{-- Client Search --}}
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <div class="relative" @click.outside="clientOpen = false">
                                <input type="hidden" name="user_id" :value="clientId">
                                <div class="relative">
                                    <input type="text"
                                           x-ref="clientInput"
                                           x-model="clientSearch"
                                           @input="clientOpen = true; clientId = ''; selectedClient = null; searchClients()"
                                           @focus="clientOpen = true; searchClients()"
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
                                <div x-show="clientOpen && clientResults.length > 0" x-cloak
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                    <template x-for="user in clientResults" :key="user.id">
                                        <div @click="selectClient(user)"
                                             class="px-4 py-2 cursor-pointer hover:bg-emerald-50 flex items-center justify-between"
                                             :class="{ 'bg-emerald-50': clientId == user.id }">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-emerald-600" x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900" x-text="user.name"></p>
                                                    <p class="text-xs text-gray-500" x-text="user.email"></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-mono font-semibold" :class="parseFloat(user.balance) > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + parseFloat(user.balance || 0).toFixed(2)"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="clientOpen && clientSearch.length >= 1 && clientResults.length === 0 && !clientId" x-cloak
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                    No clients found
                                </div>
                            </div>
                            <p class="form-hint">The client who will own this voice template</p>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />

                            {{-- Client Info Banner --}}
                            <div x-show="selectedClient" x-cloak x-transition class="mt-2 flex items-center justify-between p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <span class="text-xs font-bold text-emerald-600" x-text="selectedClient?.name?.substring(0, 2).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-emerald-800" x-text="selectedClient?.name"></p>
                                        <p class="text-xs text-emerald-600" x-text="selectedClient?.email"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-mono font-semibold" :class="selectedClient?.balance > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + (selectedClient?.balance || 0).toFixed(2)"></p>
                                    <p class="text-xs text-gray-500">Balance</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Template Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Welcome Message, Payment Reminder">
                            <p class="form-hint">A descriptive name for this voice template</p>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Audio File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-emerald-400 transition-colors">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="voice_file" class="relative cursor-pointer rounded-md font-medium text-emerald-600 hover:text-emerald-500">
                                            <span>Upload a file</span>
                                            <input id="voice_file" name="voice_file" type="file" accept=".wav,.mp3" required class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">WAV or MP3 up to 10MB</p>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('voice_file')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.voice-files.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" style="background: #059669;">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Upload Template
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Approval Notice --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Approval Required</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Pending Review</p>
                                <p class="text-xs text-amber-600">Admin will review before approval</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- File Requirements --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">File Requirements</h3>
                    </div>
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
                            <span class="font-medium text-gray-700">Under 60 seconds</span>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Tips</h3>
                    </div>
                    <div class="detail-card-body text-xs text-gray-500 space-y-2">
                        <p>Record in a quiet environment for best quality.</p>
                        <p>Speak clearly and at a moderate pace.</p>
                        <p>Files are auto-converted to Asterisk-compatible format.</p>
                        <p>Template must be approved before use in broadcasts.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
