<?php

use App\Http\Middleware\DisableBackCache;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrackPageVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register route middleware
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'disable-back-cache' => DisableBackCache::class,
        ]);

        // Apply DisableBackCache to all authenticated routes
        // This prevents back-button bypass after logout (OWASP A01)
        $middleware->appendToGroup('web', DisableBackCache::class);

        // Record successful page loads in the session trail for breadcrumbs
        $middleware->appendToGroup('web', TrackPageVisit::class);

        // Trust forwarding headers from tunnel proxies (ngrok, Cloudflare Tunnel)
        // so generated URLs use https:// when the app is reached through them
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /**
         * Handle TokenMismatchException (419 - Session Expired)
         *
         * When a user's session expires and they submit a form,
         * they'll get a 419 error. This catches that and redirects gracefully
         * with a flash message instead of showing the error page.
         *
         * OWASP A07:2021 - Identification and Authentication Failures
         */
        $exceptions->render(function (TokenMismatchException $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'message' => 'Session expired. Please log in again.',
                    'redirect' => route('login'),
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('error', 'Your session has expired. Please log in again.')
                ->with('session_expired', true);
        });

        /**
         * Handle 429 Too Many Requests from throttle middleware.
         *
         * Converts the default 429 error page into a redirect back with a flash message,
         * so users see a clear explanation instead of a raw error page.
         */
        $exceptions->render(function (ThrottleRequestsException $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'message' => 'Too many attempts. Please wait a moment before trying again.',
                ], 429);
            }

            return redirect()
                ->back()
                ->with('error', 'Too many attempts. Please wait a moment before trying again.');
        });

        /**
         * Handle 403 Forbidden errors gracefully for AJAX requests.
         */
        $exceptions->render(function (HttpException $e) {
            if ($e->getStatusCode() === 403) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([
                        'message' => 'You do not have permission to perform this action.',
                    ], 403);
                }

                return response()
                    ->view('errors.403', ['exception' => $e], 403);
            }

            if ($e->getStatusCode() === 404) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([
                        'message' => 'The requested resource was not found.',
                    ], 404);
                }

                return response()
                    ->view('errors.404', ['exception' => $e], 404);
            }

            return null;
        });

        /**
         * Handle general server errors (500) gracefully.
         * Only handles actual server errors during HTTP requests, not CLI/console.
         */
        $exceptions->render(function (Throwable $e) {
            if (app()->runningInConsole()) {
                return null;
            }

            if (app()->hasDebugModeEnabled()) {
                return null;
            }

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'message' => 'A server error occurred. Please try again later.',
                ], 500);
            }

            return response()
                ->view('errors.500', ['exception' => $e], 500);
        });
    })->create();
