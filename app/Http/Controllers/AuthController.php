<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Show the Login Form
    public function showLogin(Request $request)
    {
        // If user is already logged in, send them to the dashboard (or patients list)
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['token' => csrf_token()]);
        }

        return view('auth.login');
    }

    public function processLogin(Request $request)
    {
        // 1. Validate the Input
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // 2. Attempt to Log In
        // specific 'remember' logic handles the checkbox
        $remember = $request->boolean('remember');

        if (Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $remember)) {

            // 3. Security: Regenerate Session ID
            // (Prevents session fixation attacks)
            $request->session()->regenerate();

            // 4. Redirect User
            // 'intended' sends them to the URL they tried to visit before being intercepted by login
            // Default fallback is 'dashboard'
            return redirect()->intended(route('dashboard'));
        }

        // 5. If Login Fails...
        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Logout User
     *
     * Securely logs out the user and invalidates their session.
     *
     * Security Controls (OWASP A01 & A07):
     * 1. Auth::logout() - Logs out the user from the guard
     * 2. $request->session()->invalidate() - Destroys the session on the server
     * 3. $request->session()->regenerateToken() - Generates a new CSRF token
     * 4. Clears all session data to prevent data leakage
     *
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        // Log the logout event for audit trail
        $user = Auth::user();
        if ($user) {
            \Log::info("User logged out [User ID: {$user->id}, Username: {$user->username}]");

            // Optional: Record in audit log if using AuditLog model
            // \App\Models\AuditLog::create([
            //     'user_id' => $user->id,
            //     'action' => 'logout',
            //     'description' => 'User logged out',
            //     'ip_address' => $request->ip(),
            // ]);
        }

        // 1. Unauthenticate the user (Guard logout)
        Auth::logout();

        // 2. Completely invalidate the session on the server
        //    This ensures the session ID cannot be reused
        $request->session()->invalidate();

        // 3. Regenerate CSRF token to prevent token replay attacks
        $request->session()->regenerateToken();

        // 4. Clear all session data (additional security)
        $request->session()->flush();

        // 5. Clear browser cookies to remove any session identifiers
        // This is handled by invalidate(), but explicit is good for defense-in-depth
        $response = redirect()->route('login')
            ->with('success', 'You have been successfully logged out.');

        // Ensure no caching of the response
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        return $response;
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function submitForgotPassword(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('username', $validated['username'])->first();

        $requestRecord = PasswordResetRequest::create([
            'user_id' => $user?->id,
            'username_requested' => $validated['username'],
            'status' => 'pending',
        ]);

        // Simple non-email notification: tracked in DB for admins.
        // Optionally this can be replaced with real notifications when available.

        return redirect()->route('login')->with('success', 'Password reset request submitted. An administrator will be notified and will assist you with your login.');
    }
}
