<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KycApprovedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admin is exempt from KYC requirement
        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->kyc_status !== 'approved') {
            return redirect()->route('kyc.show')
                ->with('warning', 'Please complete KYC verification to access this feature.');
        }

        return $next($request);
    }
}
