<?php

use App\Http\Middleware\DisableBackCache;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

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
            'disable-back-cache' => DisableBackCache::class,
        ]);

        // Apply DisableBackCache to all authenticated routes
        // This prevents back-button bypass after logout (OWASP A01)
        $middleware->appendToGroup('web', DisableBackCache::class);
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
            return redirect()
                ->route('login')
                ->with('error', 'Your session has expired. Please log in again.')
                ->with('session_expired', true);
        });
    })->create();
