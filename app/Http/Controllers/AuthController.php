<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPasswordOtp;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

            // 3b. Audit trail entry for the authenticated login (no PII stored).
            $this->recordAuthAudit('login', $request, Auth::id());

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
            // Never log usernames or other personal data in application logs.
            \Log::info("User logged out [User ID: {$user->id}]");

            // Audit trail entry (no PII stored).
            $this->recordAuthAudit('logout', $request, $user->id);
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

        // Always respond the same way to avoid revealing whether a username exists.
        $user = User::where('username', $validated['username'])->first();

        if ($user && $user->email) {
            $otp = (string) random_int(100000, 999999);
            $expiresInMinutes = 15;

            DB::table('password_resets')->insert([
                'user_id' => $user->id,
                'token' => Hash::make($otp),
                'expires_at' => now()->addMinutes($expiresInMinutes),
                'used' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Mail::to($user->email)->send(new ForgotPasswordOtp($otp, $expiresInMinutes));

            session()->put('password_reset_username', $user->username);
        }

        return redirect()->route('password.forgot.verify')
            ->with('status', 'If an account exists for that username, a verification code was sent to its registered email.');
    }

    public function showForgotVerify(Request $request)
    {
        $username = session('password_reset_username');

        if (! $username) {
            return redirect()->route('password.forgot');
        }

        return view('auth.forgot-otp', ['username' => $username]);
    }

    public function submitForgotVerify(Request $request)
    {
        $sessionUsername = session('password_reset_username');

        if (! $sessionUsername) {
            return redirect()->route('password.forgot');
        }

        $validated = $request->validate([
            'username' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validated['username'] !== $sessionUsername) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $user = User::where('username', $sessionUsername)->first();

        if (! $user) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $record = DB::table('password_resets')
            ->where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($validated['otp'], $record->token)) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        DB::table('password_resets')->where('user_id', $user->id)->update(['used' => true]);
        session()->forget('password_reset_username');

        $this->recordAuthAudit('password_reset', $request, $user->id);

        return redirect()->route('login')->with('success', 'Your password has been reset. Please sign in with your new password.');
    }

    /**
     * Write an authentication event to the audit trail (never stores usernames, OTPs, or passwords).
     */
    private function recordAuthAudit(string $action, Request $request, ?int $userId): void
    {
        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => 'auth',
            'record_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
