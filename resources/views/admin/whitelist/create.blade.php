<x-admin-layout>
    <x-slot name="header">Add Whitelist Entry</x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Allow Destination Prefix</h3>
                <p class="mt-1 text-sm text-gray-500">Whitelist entries allow specific users to call destinations that may otherwise be blocked.</p>
            </div>

            <form method="POST" action="{{ route('admin.whitelist.store') }}" class="p-6 space-y-6">
                @csrf

                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                    <select id="user_id" name="user_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select a user...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="prefix" class="block text-sm font-medium text-gray-700">Prefix</label>
                    <input type="text" id="prefix" name="prefix" value="{{ old('prefix') }}" required
                           placeholder="e.g. +1800, 44"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <input type="text" id="description" name="description" value="{{ old('description') }}"
                           placeholder="e.g. Toll-free numbers"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create Entry
                    </button>
                    <a href="{{ route('admin.whitelist.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
