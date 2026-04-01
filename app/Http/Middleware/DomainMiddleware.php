<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainMiddleware
{
    /**
     * Restrict access based on domain.
     *
     * Usage: middleware('domain:admin') — only admin roles on admin domain
     *        middleware('domain:client') — only reseller/client roles on client domain
     *
     * Domains configured in config/app.php:
     *   'admin_domain' => 'admin.webvoice.net'
     *   'client_domain' => 'webvoice.net'
     *
     * If domains are not configured, middleware is bypassed (single-domain mode).
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $adminDomain = config('app.admin_domain');
        $clientDomain = config('app.client_domain');

        // If domains not configured, skip (single-domain mode)
        if (!$adminDomain || !$clientDomain) {
            return $next($request);
        }

        $currentHost = $request->getHost();
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($type === 'admin') {
            // Admin domain: only admin roles allowed
            if ($currentHost === $clientDomain && $user->isAnyAdmin()) {
                abort(403, 'Admin panel is not accessible from this domain.');
            }
        } elseif ($type === 'client') {
            // Client domain: only reseller/client roles allowed
            if ($currentHost === $adminDomain && ($user->isReseller() || $user->isClient())) {
                abort(403, 'Client panel is not accessible from this domain.');
            }
        }

        return $next($request);
    }
}
