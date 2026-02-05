<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Two-Factor Authentication</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                @if ($enabled)
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 shrink-0">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Two-factor authentication is enabled</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Your account is protected with an authenticator app. You will be asked for a
                                verification code each time you sign in.
                            </p>
                        </div>
                    </div>

                    <hr class="border-gray-200">

                    <form method="POST" action="{{ route('two-factor.disable') }}" class="space-y-4">
                        @csrf
                        @method('DELETE')

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" id="password" name="password" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <button type="submit"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                                onclick="return confirm('Are you sure you want to disable two-factor authentication?')">
                            Disable Two-Factor
                        </button>
                    </form>
                @else
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 shrink-0">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Two-factor authentication is not enabled</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Add an extra layer of security to your account by enabling two-factor authentication
                                with an authenticator app.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('two-factor.setup') }}"
                       class="inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Enable Two-Factor
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
