<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boot a session for read-only requests without persisting it.
 *
 * Endpoints wrapped in this middleware (e.g. the session-status poll) must not
 * refresh the session's last_activity, otherwise a polling page acts as a
 * keep-alive and an idle user is never logged out at the configured timeout.
 */
class ReadOnlySession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = app('session.store');

        $cookieId = (string) $request->cookies->get($session->getName(), '');

        if ($cookieId !== '') {
            $session->setId($cookieId);
        }

        $session->setRequestOnHandler($request);
        $session->start();
        $request->setLaravelSession($session);

        return $next($request);
    }
}
