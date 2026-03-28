<x-admin-layout>
    <x-slot name="header">System Settings</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">System Settings</h2>
                <p class="page-subtitle">Configure platform-wide settings and defaults</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                @foreach ($settings as $group => $items)
                    <div class="form-card">
                        <div class="form-card-header">
                            <div class="flex items-center gap-3">
                                @switch($group)
                                    @case('general')
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        @break
                                    @case('billing')
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        @break
                                    @case('sip')
                                        <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        @break
                                    @case('system')
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                            </svg>
                                        </div>
                                        @break
                                    @case('payment_gateways')
                                        <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        @break
                                    @default
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                            </svg>
                                        </div>
                                @endswitch
                                <div>
                                    <h3 class="form-card-title">{{ $group === 'payment_gateways' ? 'Payment Gateways' : ucfirst($group) . ' Settings' }}</h3>
                                    <p class="form-card-subtitle">
                                        @switch($group)
                                            @case('general')
                                                Company information and branding
                                                @break
                                            @case('billing')
                                                Invoice, payment, and pricing defaults
                                                @break
                                            @case('sip')
                                                SIP/VoIP default configurations
                                                @break
                                            @case('system')
                                                Data retention and system policies
                                                @break
                                            @case('payment_gateways')
                                                SSLCommerz and bKash gateway configuration
                                                @break
                                            @default
                                                {{ ucfirst($group) }} configuration options
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                        </div>
                        @if($group === 'payment_gateways')
                            @include('admin.settings._payment-gateways', ['items' => $items])
                        @else
                        <div class="form-card-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($items as $setting)
                                    <div class="form-group {{ in_array($setting->key, ['company_address', 'default_codec_allow']) ? 'md:col-span-2' : '' }}">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->label ?: Str::headline(str_replace('_', ' ', $setting->key)) }}
                                        </label>

                                        @if ($setting->key === 'default_codec_allow')
                                            <div x-data="{
                                                audioCodecs: [
                                                    { value: 'ulaw', desc: 'μ-law' },
                                                    { value: 'alaw', desc: 'A-law' },
                                                    { value: 'g711', desc: 'PCM' },
                                                    { value: 'g722', desc: 'HD' },
                                                    { value: 'g723', desc: '5.3k' },
                                                    { value: 'g726', desc: 'ADPCM' },
                                                    { value: 'g729', desc: '8k' },
                                                    { value: 'opus', desc: 'Adaptive' },
                                                    { value: 'gsm', desc: 'Mobile' },
                                                    { value: 'ilbc', desc: 'iLBC' },
                                                    { value: 'speex', desc: 'Open' },
                                                    { value: 'silk', desc: 'Skype' },
                                                    { value: 'siren7', desc: '16kHz' },
                                                    { value: 'siren14', desc: '32kHz' },
                                                ],
                                                videoCodecs: [
                                                    { value: 'h261', desc: 'Legacy' },
                                                    { value: 'h263', desc: 'Standard' },
                                                    { value: 'h264', desc: 'HD' },
                                                    { value: 'vp8', desc: 'WebRTC' },
                                                    { value: 'vp9', desc: 'Next-gen' },
                                                ],
                                                selected: '{{ old("settings.{$setting->key}", $setting->value) }}'.split(',').map(s => s.trim()).filter(Boolean),
                                                toggle(val) {
                                                    const idx = this.selected.indexOf(val);
                                                    if (idx > -1) { this.selected.splice(idx, 1); }
                                                    else { this.selected.push(val); }
                                                },
                                                isSelected(val) { return this.selected.includes(val); },
                                                selectAllAudio() { this.audioCodecs.forEach(c => { if (!this.isSelected(c.value)) this.selected.push(c.value); }); },
                                                selectAllVideo() { this.videoCodecs.forEach(c => { if (!this.isSelected(c.value)) this.selected.push(c.value); }); },
                                                clearAll() { this.selected = []; },
                                                get value() { return this.selected.join(','); },
                                                get count() { return this.selected.length; }
                                            }">
                                                <input type="hidden" name="settings[{{ $setting->key }}]" :value="value">

                                                {{-- Audio Codecs --}}
                                                <div class="mb-3">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Audio</span>
                                                        <button type="button" @click="selectAllAudio()" class="text-xs text-indigo-600 hover:text-indigo-800">Select all</button>
                                                    </div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <template x-for="codec in audioCodecs" :key="codec.value">
                                                            <button type="button" @click="toggle(codec.value)"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border text-xs font-medium transition-all duration-150 cursor-pointer"
                                                                :class="isSelected(codec.value)
                                                                    ? 'bg-indigo-50 border-indigo-300 text-indigo-700'
                                                                    : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-white'">
                                                                <svg x-show="isSelected(codec.value)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                                </svg>
                                                                <span class="font-mono font-semibold" x-text="codec.value"></span>
                                                                <span class="font-normal opacity-60" x-text="codec.desc"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>

                                                {{-- Video Codecs --}}
                                                <div class="mb-2">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Video</span>
                                                        <button type="button" @click="selectAllVideo()" class="text-xs text-indigo-600 hover:text-indigo-800">Select all</button>
                                                    </div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <template x-for="codec in videoCodecs" :key="codec.value">
                                                            <button type="button" @click="toggle(codec.value)"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border text-xs font-medium transition-all duration-150 cursor-pointer"
                                                                :class="isSelected(codec.value)
                                                                    ? 'bg-violet-50 border-violet-300 text-violet-700'
                                                                    : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-white'">
                                                                <svg x-show="isSelected(codec.value)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                                </svg>
                                                                <span class="font-mono font-semibold" x-text="codec.value"></span>
                                                                <span class="font-normal opacity-60" x-text="codec.desc"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>

                                                {{-- Selected count & clear --}}
                                                <div class="flex items-center justify-between mt-2">
                                                    <span class="text-xs text-gray-400" x-text="count + ' codec' + (count !== 1 ? 's' : '') + ' selected'"></span>
                                                    <button type="button" @click="clearAll()" x-show="count > 0" class="text-xs text-red-500 hover:text-red-700">Clear all</button>
                                                </div>
                                            </div>
                                        @elseif ($setting->key === 'default_currency')
                                            <select id="setting_{{ $setting->key }}"
                                                    name="settings[{{ $setting->key }}]"
                                                    class="form-input">
                                                <option value="USD" {{ $setting->value === 'USD' ? 'selected' : '' }}>USD - US Dollar ($)</option>
                                                <option value="BDT" {{ $setting->value === 'BDT' ? 'selected' : '' }}>BDT - Bangladeshi Taka (৳)</option>
                                            </select>
                                        @elseif ($setting->key === 'default_billing_type')
                                            <select id="setting_{{ $setting->key }}"
                                                    name="settings[{{ $setting->key }}]"
                                                    class="form-input">
                                                <option value="prepaid" {{ $setting->value === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                                <option value="postpaid" {{ $setting->value === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                                            </select>
                                        @elseif ($setting->type === 'boolean')
                                            <div class="flex items-center gap-3 mt-2">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                                    <input type="checkbox"
                                                           id="setting_{{ $setting->key }}"
                                                           name="settings[{{ $setting->key }}]"
                                                           value="1"
                                                           {{ $setting->value ? 'checked' : '' }}
                                                           class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                                </label>
                                                <span class="text-sm text-gray-600">{{ $setting->value ? 'Enabled' : 'Disabled' }}</span>
                                            </div>
                                        @elseif ($setting->type === 'integer')
                                            <input type="number"
                                                   id="setting_{{ $setting->key }}"
                                                   name="settings[{{ $setting->key }}]"
                                                   value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                                   step="1"
                                                   class="form-input">
                                        @elseif ($setting->type === 'float')
                                            <input type="number"
                                                   id="setting_{{ $setting->key }}"
                                                   name="settings[{{ $setting->key }}]"
                                                   value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                                   step="0.01"
                                                   class="form-input">
                                        @elseif ($setting->key === 'company_address')
                                            <textarea id="setting_{{ $setting->key }}"
                                                      name="settings[{{ $setting->key }}]"
                                                      rows="2"
                                                      class="form-input">{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
                                        @else
                                            @php $isSecret = Str::contains($setting->key, ['password', 'secret']); @endphp
                                            <input type="{{ $isSecret ? 'password' : 'text' }}"
                                                   id="setting_{{ $setting->key }}"
                                                   name="settings[{{ $setting->key }}]"
                                                   value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                                   {!! $isSecret && $setting->value ? 'placeholder="••••••••  (leave blank to keep current)"' : '' !!}
                                                   class="form-input {{ in_array($setting->key, ['codec_allow', 'invoice_prefix']) ? 'font-mono' : '' }} {{ $isSecret ? 'font-mono' : '' }}"
                                                   autocomplete="off">
                                        @endif

                                        @if ($setting->description)
                                            <p class="form-hint">{{ $setting->description }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                @endforeach

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Settings
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Quick Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">About Settings</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800">Platform Configuration</p>
                                <p class="text-xs text-indigo-600">Settings apply system-wide</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Changes take effect immediately</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Cached for 5 minutes</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All changes are logged</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Setting Groups --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Setting Groups</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">General</span>
                            </div>
                            <p class="text-xs text-gray-500">Company name, address, contact info used in invoices and emails.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-warning">System</span>
                            </div>
                            <p class="text-xs text-gray-500">Data retention, session timeout, API access, and maintenance mode.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">SIP</span>
                            </div>
                            <p class="text-xs text-gray-500">Default codecs, channel limits, and call handling settings.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-success">Billing</span>
                            </div>
                            <p class="text-xs text-gray-500">Invoice prefix, tax rates, currency, low balance thresholds.</p>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Tips</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Invoice prefix change won't affect existing invoices</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Retention days affects data purge schedule</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Low balance threshold triggers email alerts</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Codec order determines preference priority</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Last Updated --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Audit Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-2 text-sm">
                            @php
                                $lastUpdate = $settings->flatten()->sortByDesc('updated_at')->first();
                            @endphp
                            <div class="flex justify-between">
                                <span class="text-gray-500">Last Modified</span>
                                <span class="text-gray-900 font-medium">{{ $lastUpdate?->updated_at?->diffForHumans() ?? 'Never' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Total Settings</span>
                                <span class="text-gray-900 font-medium">{{ $settings->flatten()->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
