<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Payment Result' }} — rSwitch</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        @php
            $iconBg = [
                'success' => 'bg-emerald-50 text-emerald-600',
                'cancelled' => 'bg-amber-50 text-amber-600',
                'failed' => 'bg-red-50 text-red-600',
                'error' => 'bg-red-50 text-red-600',
            ][$status] ?? 'bg-gray-100 text-gray-500';
            $icon = match ($status) {
                'success' => 'M5 13l4 4L19 7',
                'cancelled' => 'M6 18L18 6M6 6l12 12',
                default => 'M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z',
            };
        @endphp
        <div class="w-16 h-16 rounded-full {{ $iconBg }} mx-auto mb-5 flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
            </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-900 mb-2">{{ $title }}</h1>
        <p class="text-gray-600 mb-2">{{ $message }}</p>

        @if(($status ?? '') === 'success' && isset($amount))
            <p class="text-2xl font-semibold text-gray-900 mt-4 mb-1">
                {{ $currency ?? 'BDT' }} {{ number_format((float) $amount, 2) }}
            </p>
        @endif

        <a href="{{ $continueUrl }}"
           class="mt-6 inline-flex items-center justify-center w-full py-3 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">
            Return to Panel
        </a>
    </div>
</body>
</html>
