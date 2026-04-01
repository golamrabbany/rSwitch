<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'rSwitch') }}</title>

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
        .fade-enter { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="login-gradient min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md" x-data="loginApp()">
        {{-- Logo & Title --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">{{ config('app.name', 'rSwitch') }}</h1>
            <p class="text-white/70 mt-1" x-text="step === 'credentials' ? 'Sign in to your account' : 'Enter verification code'"></p>
        </div>

        {{-- Login Card --}}
        <div class="glass-card rounded-2xl p-8">

            {{-- ========== STEP 1: Email + Password ========== --}}
            <div x-show="step === 'credentials'" x-transition>
                <div class="text-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Welcome Back</h2>
                    <p class="text-gray-500 text-sm mt-1">Enter your credentials to continue</p>
                </div>

                {{-- Error Message --}}
                <div x-show="error" x-cloak class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200">
                    <div class="flex items-center gap-2 text-red-700">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm" x-text="error"></span>
                    </div>
                </div>

                <form @submit.prevent="submitCredentials">
                    {{-- Email --}}
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input x-model="email" type="email" id="email" required autofocus autocomplete="username"
                                   placeholder="you@example.com"
                                   class="block w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input x-model="password" type="password" id="password" required autocomplete="current-password"
                                   placeholder="Enter your password"
                                   class="block w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    {{-- Remember Me & Forgot Password --}}
                    <div class="flex items-center justify-between mb-6">
                        <label class="inline-flex items-center">
                            <input x-model="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ms-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <a class="text-sm text-indigo-600 hover:text-indigo-800 font-medium" href="{{ route('password.request') }}">
                            Forgot password?
                        </a>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" :disabled="loading"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-60">
                        <template x-if="!loading">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                        </template>
                        <template x-if="loading">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="loading ? 'Verifying...' : 'Sign In'"></span>
                    </button>
                </form>

                {{-- Register Link (hide on admin domain) --}}
                @if(request()->getHost() !== config('app.admin_domain'))
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        Don't have an account? <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Sign Up</a>
                    </p>
                </div>
                @endif
            </div>

            {{-- ========== STEP 2: OTP Verification ========== --}}
            <div x-show="step === 'otp'" x-cloak class="fade-enter">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 mb-4">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900">Enter Verification Code</h2>
                    <p class="text-gray-500 text-sm mt-1">OTP sent to <span class="font-medium text-gray-700" x-text="maskedEmail"></span></p>
                </div>

                {{-- OTP Display (test mode) --}}
                <template x-if="otpDisplay">
                    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border-2 border-emerald-200 border-dashed">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            <span class="text-sm font-medium text-emerald-700">Test Mode - Your OTP:</span>
                        </div>
                        <div class="text-center">
                            <span class="text-3xl font-bold text-emerald-700 bg-white px-6 py-3 rounded-lg inline-block font-mono tracking-widest" x-text="otpDisplay"></span>
                        </div>
                    </div>
                </template>

                {{-- Success Message --}}
                <div x-show="success" x-cloak class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200">
                    <div class="flex items-center gap-2 text-green-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm" x-text="success"></span>
                    </div>
                </div>

                {{-- Error Message --}}
                <div x-show="error" x-cloak class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200">
                    <div class="flex items-center gap-2 text-red-700">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm" x-text="error"></span>
                    </div>
                </div>

                <form @submit.prevent="submitOtp">
                    {{-- OTP Input --}}
                    <div class="mb-6">
                        <input x-model="otp" x-ref="otpInput" type="text" maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                               autocomplete="one-time-code" required autofocus placeholder="000000"
                               @input="otp = $el.value.replace(/[^0-9]/g, '')"
                               class="otp-input block w-full py-4 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                    </div>

                    {{-- Submit --}}
                    <button type="submit" :disabled="loading || otp.length !== 6"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-60">
                        <template x-if="!loading">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                        <template x-if="loading">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="loading ? 'Verifying...' : 'Verify & Sign In'"></span>
                    </button>
                </form>

                {{-- Actions --}}
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                    <div class="relative flex justify-center text-sm"><span class="px-4 bg-white text-gray-500">Options</span></div>
                </div>

                <div class="flex items-center justify-center gap-4">
                    <button @click.prevent="resendOtp" :disabled="resendCooldown > 0" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm transition-colors disabled:text-gray-400">
                        <span x-show="resendCooldown === 0">Resend OTP</span>
                        <span x-show="resendCooldown > 0" x-text="'Resend in ' + resendCooldown + 's'"></span>
                    </button>
                    <span class="text-gray-300">|</span>
                    <button @click.prevent="backToCredentials" class="text-gray-500 hover:text-gray-700 font-medium text-sm transition-colors">
                        Back to Login
                    </button>
                </div>

                {{-- Timer Notice --}}
                <div class="mt-6 flex items-start gap-3 p-4 rounded-lg bg-amber-50 border border-amber-200">
                    <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-amber-700 leading-relaxed">
                        The verification code expires in <strong>5 minutes</strong>. Click "Resend OTP" if your code has expired.
                    </p>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <p class="text-center text-white/60 text-sm mt-6">
            &copy; {{ date('Y') }} {{ config('app.name', 'rSwitch') }}. All rights reserved.
        </p>
    </div>

    <script>
        function loginApp() {
            return {
                step: 'credentials',
                email: '',
                password: '',
                remember: false,
                otp: '',
                loading: false,
                error: '',
                success: '',
                maskedEmail: '',
                otpDisplay: null,
                resendCooldown: 0,
                resendTimer: null,

                async submitCredentials() {
                    this.error = '';
                    this.loading = true;

                    try {
                        const res = await fetch('{{ route("login.validate") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                email: this.email,
                                password: this.password,
                                remember: this.remember,
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.maskedEmail = data.masked_email;
                            this.otpDisplay = data.otp_display;
                            this.step = 'otp';
                            this.startResendCooldown();
                            this.$nextTick(() => this.$refs.otpInput?.focus());
                        } else {
                            this.error = data.message;
                        }
                    } catch (e) {
                        this.error = 'Network error. Please try again.';
                    }

                    this.loading = false;
                },

                async submitOtp() {
                    this.error = '';
                    this.success = '';
                    this.loading = true;

                    try {
                        const res = await fetch('{{ route("login.verify-otp") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ otp: this.otp }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            if (data.expired) {
                                this.backToCredentials();
                                this.error = data.message;
                            } else {
                                this.error = data.message;
                            }
                            this.otp = '';
                        }
                    } catch (e) {
                        this.error = 'Network error. Please try again.';
                    }

                    this.loading = false;
                },

                async resendOtp() {
                    this.error = '';
                    this.success = '';

                    try {
                        const res = await fetch('{{ route("login.resend-otp") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.success = data.message;
                            this.otpDisplay = data.otp_display;
                            this.otp = '';
                            this.startResendCooldown();
                        } else {
                            if (data.expired) {
                                this.backToCredentials();
                            }
                            this.error = data.message;
                        }
                    } catch (e) {
                        this.error = 'Network error. Please try again.';
                    }
                },

                backToCredentials() {
                    this.step = 'credentials';
                    this.otp = '';
                    this.error = '';
                    this.success = '';
                    this.otpDisplay = null;
                    if (this.resendTimer) clearInterval(this.resendTimer);
                },

                startResendCooldown() {
                    this.resendCooldown = 60;
                    if (this.resendTimer) clearInterval(this.resendTimer);
                    this.resendTimer = setInterval(() => {
                        this.resendCooldown--;
                        if (this.resendCooldown <= 0) clearInterval(this.resendTimer);
                    }, 1000);
                },
            };
        }
    </script>
</body>
</html>
