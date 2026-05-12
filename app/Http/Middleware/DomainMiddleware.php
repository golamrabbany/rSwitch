<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainMiddleware
{
    /**
     * Strict role-vs-host enforcement:
     *
     *   admin / super_admin / recharge_admin → only on admin.webvoice.net
     *   reseller                              → only on reseller.webvoice.net
     *   client                                → only on client.webvoice.net
     *
     * The `$type` argument names the EXPECTED audience for the route group:
     *   domain:admin    — admin-only routes
     *   domain:reseller — reseller-only routes
     *   domain:client   — client-only routes
     *
     * If the request comes in on the wrong host, we redirect to the host the
     * user's role is allowed on (silent UX recovery — no 403 page).
     *
     * Bypassed in single-domain mode (any of the three domain configs missing).
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $domains = [
            'admin' => config('app.admin_domain'),
            'reseller' => config('app.reseller_domain'),
            'client' => config('app.client_domain'),
        ];

        // Single-domain mode (local dev / tests): skip enforcement entirely.
        if (in_array(null, $domains, true) || in_array('', $domains, true)) {
            return $next($request);
        }

        $expectedHost = $domains[$type] ?? null;
        if ($expectedHost === null) {
            return $next($request);
        }

        $currentHost = $request->getHost();
        if ($currentHost === $expectedHost) {
            return $next($request);
        }

        // Wrong host for this route. Redirect to the same path on the right host.
        return redirect()->away("https://{$expectedHost}{$request->getRequestUri()}");
    }
}
