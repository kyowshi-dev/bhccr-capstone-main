<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * Report whether the current session is still authenticated.
     *
     * The route uses the ReadOnlySession middleware, so polling this endpoint
     * never refreshes the session's idle clock.
     */
    public function status(): JsonResponse
    {
        $active = Auth::check();

        return response()->json([
            'active' => $active,
            'lifetime_minutes' => (int) config('session.lifetime'),
            'user' => $active ? Auth::user()?->only(['id', 'username']) : null,
        ]);
    }

    /**
     * Refresh the session's idle clock.
     *
     * The frontend only calls this while the user is demonstrably interacting
     * with the page, so an open-but-idle tab can no longer keep the session
     * alive indefinitely.
     */
    public function heartbeat(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
