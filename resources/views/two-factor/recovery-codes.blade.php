<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recovery Codes</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Save these recovery codes</h3>
                            <p class="mt-1 text-sm text-yellow-700">
                                Store them securely. Each code can only be used once. If you lose access to your
                                authenticator app, use one of these codes to sign in.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-md bg-gray-100 p-4">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($recoveryCodes as $code)
                            <code class="text-sm font-mono text-gray-800">{{ $code }}</code>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end">
                    <a href="{{ route('two-factor.status') }}"
                       class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        I've saved my codes
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
