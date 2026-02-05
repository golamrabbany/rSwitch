<x-admin-layout>
    <x-slot name="header">System Settings</x-slot>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($settings as $group => $items)
                <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                    <h3 class="text-base font-semibold text-gray-900 capitalize">{{ $group }} Settings</h3>

                    <div class="space-y-4">
                        @foreach ($items as $setting)
                            <div>
                                <label for="setting_{{ $setting->key }}" class="block text-sm font-medium text-gray-700">
                                    {{ $setting->label ?: $setting->key }}
                                </label>

                                @if ($setting->type === 'boolean')
                                    <select id="setting_{{ $setting->key }}" name="settings[{{ $setting->key }}]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="1" {{ $setting->value ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !$setting->value ? 'selected' : '' }}>No</option>
                                    </select>
                                @elseif ($setting->type === 'integer')
                                    <input type="number" id="setting_{{ $setting->key }}" name="settings[{{ $setting->key }}]"
                                           value="{{ old("settings.{$setting->key}", $setting->value) }}" step="1"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @elseif ($setting->type === 'float')
                                    <input type="number" id="setting_{{ $setting->key }}" name="settings[{{ $setting->key }}]"
                                           value="{{ old("settings.{$setting->key}", $setting->value) }}" step="0.01"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @else
                                    <input type="text" id="setting_{{ $setting->key }}" name="settings[{{ $setting->key }}]"
                                           value="{{ old("settings.{$setting->key}", $setting->value) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @endif

                                @if ($setting->description)
                                    <p class="mt-1 text-xs text-gray-500">{{ $setting->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex items-center justify-end">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
