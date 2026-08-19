<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders Middleware
 *
 * Adds hardening response headers on every web request:
 * - X-Frame-Options DENY + frame-ancestors 'none': clickjacking protection
 * - X-Content-Type-Options nosniff: MIME sniffing protection
 * - Referrer-Policy: limits referrer leakage
 * - Permissions-Policy: disables high-privilege browser features
 * - Strict-Transport-Security: HTTPS-only (only sent over HTTPS)
 * - Content-Security-Policy: XSS mitigation
 *
 * OWASP A05:2021 - Security Misconfiguration
 *
 * CSP compatibility debt: the Blade views use inline style attributes and
 * some inline <script> blocks, and Alpine.js evaluates its expressions via
 * the AsyncFunction constructor, so 'unsafe-inline' and 'unsafe-eval' are
 * allowed for script-src and style-src. Remove them incrementally as views
 * are refactored to external stylesheets/scripts and Alpine's CSP build
 * (@alpinejs/csp) can support the app's expressions.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // When the Vite dev server is running (public/hot present), @vite injects
        // asset URLs from the dev server origin. The CSP must allow that plain-HTTP
        // origin (plus its HMR websocket) or every stylesheet/script silently gets
        // blocked and interactive pages break. Production builds have no hot file,
        // so this never widens the policy outside local development.
        $devOrigin = '';
        if (app()->environment('local') && is_file(public_path('hot'))) {
            $devOrigin = ' http://127.0.0.1:5173 http://localhost:5173';
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:{$devOrigin}",
            "style-src 'self' 'unsafe-inline' https:{$devOrigin}",
            "img-src 'self' data: https:",
            "font-src 'self' https: data:{$devOrigin}",
            "connect-src 'self' ws://127.0.0.1:5173 ws://localhost:5173{$devOrigin}",
        ]));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
