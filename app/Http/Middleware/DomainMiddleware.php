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
     * domain:admin — these routes only accessible on admin domain
     * domain:client — these routes only accessible on client domain
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

        if ($type === 'admin') {
            // Admin routes: block if accessed from client domain
            if ($currentHost === $clientDomain) {
                abort(403, 'Admin panel is not accessible from this domain.');
            }
        } elseif ($type === 'client') {
            // Client/reseller routes: block if accessed from admin domain
            if ($currentHost === $adminDomain) {
                abort(403, 'Client panel is not accessible from this domain.');
            }
        }

        return $next($request);
    }
}
