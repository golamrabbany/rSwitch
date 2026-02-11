@php
    use App\Http\Controllers\Admin\ImpersonationController;
    $isImpersonating = ImpersonationController::isImpersonating();
    $impersonatorName = ImpersonationController::getImpersonatorName();
@endphp

@if($isImpersonating)
    <div class="fixed bottom-0 left-0 right-0 z-50 bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-white/20 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="font-semibold">Impersonation Mode</span>
                        <span class="mx-2">|</span>
                        <span>Viewing as <strong>{{ auth()->user()->name }}</strong> ({{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }})</span>
                    </div>
                </div>
                <form action="{{ route('admin.impersonate.stop') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-orange-600 font-semibold rounded-lg hover:bg-orange-50 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                        </svg>
                        Back to {{ $impersonatorName ?? 'Admin' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    {{-- Add padding to body so content isn't hidden behind the banner --}}
    <style>
        body { padding-bottom: 60px !important; }
    </style>
@endif
