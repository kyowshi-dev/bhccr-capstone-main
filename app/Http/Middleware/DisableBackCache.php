<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DisableBackCache Middleware
 *
 * Prevents the browser's back button from displaying cached sensitive data
 * after a user has logged out or their session has expired.
 *
 * OWASP A01:2021 - Broken Access Control
 * OWASP A07:2021 - Identification and Authentication Failures
 *
 * Sets Cache-Control headers to:
 * - no-cache: Browser must revalidate with server (no stale cache)
 * - no-store: Don't store in browser cache at all
 * - must-revalidate: Proxy must revalidate (no stale responses)
 * - max-age=0: Expires immediately
 * - private: Not cacheable by shared caches
 */
class DisableBackCache
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Set Cache-Control header to prevent back-button bypass
        // Use headers->set() instead of header() for compatibility with all response types (including StreamedResponse)
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');

        // Additional security headers
        $response->headers->set('Pragma', 'no-cache');                    // For HTTP/1.0 compatibility
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');  // Force expiration in the past

        return $response;
    }
}
