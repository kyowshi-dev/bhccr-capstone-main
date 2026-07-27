<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        if (! empty($roles) && method_exists($user, 'hasRole') && ! $user->hasRole(...$roles)) {
            abort(403);
        }

        return $next($request);
    }
}
