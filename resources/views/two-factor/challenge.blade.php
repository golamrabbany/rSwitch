<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Two-Factor Authentication</h2>
        <p>Enter the 6-digit code from your authenticator app, or use one of your recovery codes.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Authentication Code')" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-lg tracking-widest"
                          type="text" name="code" required autofocus
                          autocomplete="one-time-code" inputmode="numeric" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 underline mr-3">
                Cancel
            </a>
            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
