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
     * domain:admin  — only served from admin domain (keeps admin URLs off the
     *                 customer-facing host)
     * domain:client — served from BOTH domains; admin domain is treated as a
     *                 superset so impersonation can stay on a single host
     *
     * If domains are not configured, middleware is bypassed (single-domain mode).
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $adminDomain = config('app.admin_domain');
        $clientDomain = config('app.client_domain');

        if (!$adminDomain || !$clientDomain) {
            return $next($request);
        }

        if ($type === 'admin' && $request->getHost() === $clientDomain) {
            abort(403, 'Admin panel is not accessible from this domain.');
        }

        return $next($request);
    }
}
