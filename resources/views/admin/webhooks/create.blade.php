<x-admin-layout>
    <x-slot name="header">Create Webhook Endpoint</x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.webhooks.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                    <select id="user_id" name="user_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select user...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }}) — {{ $user->role }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                </div>

                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700">Webhook URL</label>
                    <input type="url" id="url" name="url" value="{{ old('url') }}" required placeholder="https://example.com/webhook"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <x-input-error :messages="$errors->get('url')" class="mt-2" />
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="Optional description..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Events</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($events as $key => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="events[]" value="{{ $key }}"
                                       {{ in_array($key, old('events', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('events')" class="mt-2" />
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Active
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.webhooks.index') }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Create Endpoint</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
