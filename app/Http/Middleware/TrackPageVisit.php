<?php

namespace App\Http\Middleware;

use App\Helpers\BreadcrumbHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackPageVisit Middleware
 *
 * Records successful HTML page loads in the session-backed breadcrumb
 * trail so the sidebar breadcrumbs reflect the user's actual navigation
 * path instead of a hardcoded hierarchy.
 *
 * Only plain GET page loads are tracked: AJAX polls, exports, prints,
 * downloads, and non-page routes (search, session, livewire) are ignored.
 */
class TrackPageVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            BreadcrumbHelper::recordCurrentVisit();
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        if (! $response->isSuccessful() || $response->isRedirection()) {
            return false;
        }

        return BreadcrumbHelper::isPageRoute($request->route()?->getName());
    }
}
