<x-client-layout>
    <x-slot name="header">Edit SIP Account: {{ $sipAccount->username }}</x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('client.sip-accounts.update', $sipAccount) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username (SIP ID)</label>
                    <p class="mt-1 text-sm font-mono text-gray-900 bg-gray-50 rounded-md px-3 py-2">{{ $sipAccount->username }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <p class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $sipAccount->status === 'active' ? 'bg-green-100 text-green-800' : ($sipAccount->status === 'suspended' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($sipAccount->status) }}
                        </span>
                    </p>
                </div>

                <hr class="border-gray-200">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">SIP Password</label>
                    <input type="text" id="password" name="password" value="{{ old('password') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                           placeholder="Leave blank to keep current password">
                    <p class="mt-1 text-xs text-gray-500">Minimum 6 characters.</p>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="caller_id_name" class="block text-sm font-medium text-gray-700">Caller ID Name</label>
                        <input type="text" id="caller_id_name" name="caller_id_name" value="{{ old('caller_id_name', $sipAccount->caller_id_name) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('caller_id_name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="caller_id_number" class="block text-sm font-medium text-gray-700">Caller ID Number</label>
                        <input type="text" id="caller_id_number" name="caller_id_number" value="{{ old('caller_id_number', $sipAccount->caller_id_number) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('caller_id_number')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('client.sip-accounts.show', $sipAccount) }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Update SIP Account
                </button>
            </div>
        </form>
    </div>
</x-client-layout>
