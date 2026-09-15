<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->user() !== null) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
