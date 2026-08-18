<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordReset,
        private readonly AuditLogService $audit,
    ) {}

    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['token' => csrf_token()]);
        }

        return view('auth.login');
    }

    public function processLogin(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('username', $credentials['username'])->first();

        if ($user && $user->isLocked()) {
            $this->audit->log('login_blocked_locked', 'auth', $request, $user->id);

            return back()
                ->withErrors([
                    'username' => 'This account is temporarily locked due to too many failed login attempts.',
                ])
                ->with('locked_until', $user->locked_until->timestamp)
                ->onlyInput('username');
        }

        $remember = $request->boolean('remember');

        if ($user && Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $remember)) {

            $user->clearLoginFailures();

            $request->session()->regenerate();

            $this->audit->log('login', 'auth', $request, Auth::id());

            return redirect()->intended(route('dashboard'));
        }

        if ($user) {
            $user->registerLoginFailure();

            $this->audit->log('login_failed', 'auth', $request, $user->id);

            if ($user->isLocked()) {
                return back()
                    ->withErrors([
                        'username' => 'This account is temporarily locked due to too many failed login attempts.',
                    ])
                    ->with('locked_until', $user->locked_until->timestamp)
                    ->onlyInput('username');
            }

            $maxAttempts = (int) ApplicationSetting::get('login_max_attempts', 5);
            $remaining = max(1, $maxAttempts - $user->failed_login_attempts);

            return back()->withErrors([
                'username' => 'The provided credentials do not match our records. '
                    .$remaining.' '.Str::plural('attempt', $remaining)
                    .' remaining before your account is temporarily locked.',
            ])->onlyInput('username');
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Logout User
     *
     * Securely logs out the user and invalidates their session.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            \Log::info("User logged out [User ID: {$user->id}]");

            $this->audit->log('logout', 'auth', $request, $user->id);
        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $request->session()->flush();

        $response = redirect()->route('login')
            ->with('success', 'You have been successfully logged out.');

        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        return $response;
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function submitForgotPassword(ForgotPasswordRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('username', $validated['username'])->first();

        if ($user && $user->email && ! $this->passwordReset->issueOtp($user)) {
            return back()->withInput()->withErrors([
                'username' => 'We could not send the verification code right now. Please try again in a few minutes.',
            ]);
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

        $maskedEmail = null;

        $user = User::where('username', $username)->first();

        if ($user && $user->email) {
            $maskedEmail = $this->maskEmail($user->email);
        }

        return view('auth.forgot-otp', [
            'username' => $username,
            'maskedEmail' => $maskedEmail,
        ]);
    }

    public function submitForgotVerify(ResetPasswordRequest $request)
    {
        $sessionUsername = session('password_reset_username');

        if (! $sessionUsername) {
            return redirect()->route('password.forgot');
        }

        $validated = $request->validated();

        if ($validated['username'] !== $sessionUsername) {
            return back()->withInput()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $user = $this->passwordReset->verifyAndReset(
            $sessionUsername,
            $validated['otp'],
            $validated['password']
        );

        if (! $user) {
            return back()->withInput()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $this->audit->log('password_reset', 'auth', $request, $user->id);

        return redirect()->route('login')->with('success', 'Your password has been reset. Please sign in with your new password.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        $visibleLength = max(strlen($local) > 2 ? 2 : 1, 0);
        $maskedLocal = substr($local, 0, $visibleLength).str_repeat('*', max(strlen($local) - $visibleLength, 1));

        return $maskedLocal.'@'.$domain;
    }
}
