{{-- Payment Gateways — each gateway in its own sub-card --}}
@php
    // Group settings by gateway prefix
    $gateways = collect($items)->groupBy(function ($setting) {
        return Str::before($setting->key, '_');
    });

    $gatewayMeta = [
        'sslcommerz' => [
            'name' => 'SSLCommerz',
            'desc' => 'Bangladesh payment gateway — Cards, Mobile Banking, Internet Banking',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
            'bg' => '#e0e7ff', // indigo-100
            'fg' => '#4f46e5', // indigo-600
        ],
        'bkash' => [
            'name' => 'bKash',
            'desc' => 'Bangladesh mobile financial service — Tokenized Checkout',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
            'bg' => '#fce7f3', // pink-100
            'fg' => '#db2777', // pink-600
        ],
    ];
@endphp

<div class="form-card-body p-0">
    <div class="divide-y divide-gray-100">
        @foreach($gateways as $prefix => $settings)
            @php
                $meta = $gatewayMeta[$prefix] ?? ['name' => ucfirst($prefix), 'desc' => ucfirst($prefix) . ' gateway', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>', 'bg' => '#f3f4f6', 'fg' => '#4b5563'];
                $enableKey = $prefix . '_enabled';
                $enableSetting = $settings->firstWhere('key', $enableKey);
                $configSettings = $settings->reject(fn($s) => $s->key === $enableKey);
                $isEnabled = $enableSetting?->value ? true : false;
            @endphp

            <div class="p-5" x-data="{ enabled: {{ $isEnabled ? 'true' : 'false' }} }">
                {{-- Gateway Header with Toggle --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: {{ $meta['bg'] }}; color: {{ $meta['fg'] }};">
                            {!! $meta['icon'] !!}
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">{{ $meta['name'] }}</h4>
                            <p class="text-xs text-gray-500">{{ $meta['desc'] }}</p>
                        </div>
                    </div>

                    @if($enableSetting)
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="settings[{{ $enableKey }}]" value="0">
                            <input type="checkbox"
                                   name="settings[{{ $enableKey }}]"
                                   value="1"
                                   {{ $isEnabled ? 'checked' : '' }}
                                   @change="enabled = $el.checked"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ml-2 text-xs font-medium" :class="enabled ? 'text-emerald-600' : 'text-gray-400'" x-text="enabled ? 'Enabled' : 'Disabled'"></span>
                        </label>
                    @endif
                </div>

                {{-- Gateway Config Fields (collapsed when disabled) --}}
                <div x-show="enabled" x-collapse x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                        @foreach($configSettings as $setting)
                            <div class="form-group">
                                <label for="setting_{{ $setting->key }}" class="form-label">
                                    {{ $setting->label ?: Str::headline(str_replace($prefix . '_', '', $setting->key)) }}
                                </label>

                                @if($setting->type === 'boolean')
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
                                @else
                                    @php $isSecret = Str::contains($setting->key, ['password', 'secret']); @endphp
                                    @if($isSecret)
                                        <div class="relative" x-data="{ show: false }">
                                            <input :type="show ? 'text' : 'password'"
                                                   id="setting_{{ $setting->key }}"
                                                   name="settings[{{ $setting->key }}]"
                                                   value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                                   {!! $setting->value ? 'placeholder="••••••••  (leave blank to keep current)"' : '' !!}
                                                   class="form-input font-mono pr-10"
                                                   autocomplete="off">
                                            <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                        </div>
                                    @else
                                        <input type="text"
                                               id="setting_{{ $setting->key }}"
                                               name="settings[{{ $setting->key }}]"
                                               value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                               class="form-input font-mono"
                                               autocomplete="off">
                                    @endif
                                @endif

                                @if($setting->description)
                                    <p class="form-hint">{{ $setting->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Disabled state info --}}
                <div x-show="!enabled" x-cloak class="mt-2 text-xs text-gray-400">
                    Enable this gateway to configure credentials.
                </div>
            </div>
        @endforeach
    </div>
</div>
