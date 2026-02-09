<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verify OTP - {{ config('app.name', 'rSwitch') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        .login-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .otp-input {
            letter-spacing: 0.75em;
            text-align: center;
            font-size: 1.75rem;
            font-weight: 600;
            padding-left: 0.75em;
        }
        .otp-input::placeholder {
            letter-spacing: 0.3em;
            font-size: 1.25rem;
        }
        .otp-display {
            font-family: 'Monaco', 'Consolas', monospace;
            letter-spacing: 0.5em;
        }
    </style>
</head>
<body class="login-gradient min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        {{-- Logo & Title --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Verify OTP</h1>
            <p class="text-white/70 mt-1">Enter the code to complete login</p>
        </div>

        {{-- Verification Card --}}
        <div class="glass-card rounded-2xl p-8">
            {{-- OTP Display for Testing --}}
            @if($otp_display ?? false)
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border-2 border-emerald-200 border-dashed">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span class="text-sm font-medium text-emerald-700">Test Mode - Your OTP:</span>
                    </div>
                    <div class="text-center">
                        <span class="otp-display text-3xl font-bold text-emerald-700 bg-white px-6 py-3 rounded-lg inline-block">
                            {{ $otp_display }}
                        </span>
                    </div>
                    <p class="text-xs text-emerald-600 text-center mt-2">Copy this code and enter below</p>
                </div>
            @endif

            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 mb-4">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900">Enter Verification Code</h2>
                <p class="text-gray-500 text-sm mt-1">Enter the 6-digit code shown above</p>
            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200">
                    <div class="flex items-center gap-2 text-green-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-center gap-2 text-red-700">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.otp.verify') }}">
                @csrf

                {{-- OTP Input --}}
                <div class="mb-6">
                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        placeholder="000000"
                        class="otp-input block w-full py-4 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        x-data
                        x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                    >
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center justify-center mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-600">Remember this device</span>
                    </label>
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Verify & Sign In
                </button>
            </form>

            {{-- Divider --}}
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500">Options</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-center gap-4">
                <form method="POST" action="{{ route('admin.otp.regenerate') }}">
                    @csrf
                    <button type="submit" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm transition-colors">
                        Generate New OTP
                    </button>
                </form>

                <span class="text-gray-300">|</span>

                <a href="{{ route('admin.login') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm transition-colors">
                    Back to Login
                </a>
            </div>

            {{-- Timer Notice --}}
            <div class="mt-6 flex items-start gap-3 p-4 rounded-lg bg-amber-50 border border-amber-200">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-amber-700 leading-relaxed">
                    The verification code expires in <strong>5 minutes</strong>. Click "Generate New OTP" if your code has expired.
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center text-white/60 text-sm mt-6">
            &copy; {{ date('Y') }} {{ config('app.name', 'rSwitch') }}. All rights reserved.
        </p>
    </div>
</body>
</html>
