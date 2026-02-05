<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Set Up Two-Factor Authentication</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Scan QR Code</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)
                    </p>
                </div>

                <div class="flex justify-center">
                    <div class="p-4 bg-white border rounded-lg">
                        {!! \BaconQrCode\Renderer\Image\SvgImageBackEnd::class
                            ? (new \BaconQrCode\Writer(
                                new \BaconQrCode\Renderer\ImageRenderer(
                                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                                )
                            ))->writeString($qrUrl)
                            : '' !!}
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Or enter this key manually:</p>
                    <div class="mt-2 flex items-center">
                        <code class="flex-1 block rounded-md bg-gray-100 px-3 py-2 text-sm font-mono tracking-widest text-gray-900">{{ $secret }}</code>
                    </div>
                </div>

                <hr class="border-gray-200">

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Verification Code</label>
                        <p class="text-xs text-gray-500">Enter the 6-digit code from your authenticator app to confirm setup.</p>
                        <input type="text" id="code" name="code" required autofocus
                               maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"
                               class="mt-2 block w-48 rounded-md border-gray-300 text-center text-lg tracking-widest shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Verify &amp; Enable
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
