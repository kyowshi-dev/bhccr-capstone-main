@extends('layouts.app')

@section('title', 'Security Settings')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Back Button -->
    <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-2 text-sm font-medium" style="color: var(--primary);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Profile
    </a>

    <!-- Security Settings Form -->
    <div class="rounded-2xl p-6 border border-[var(--border)]" style="background: var(--bg-surface-elevated); box-shadow: var(--shadow-sm);">
        <h1 class="text-2xl font-display font-semibold mb-2" style="color: var(--ink);">Security Settings</h1>
        <p class="text-sm mb-6" style="color: var(--ink-subtle);">
            Configure session, login lockout, and password policy for all users in the system.
        </p>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg" style="background: rgba(196, 92, 65, 0.1); border: 1px solid rgba(196, 92, 65, 0.2);">
                <h3 class="font-semibold text-sm mb-2" style="color: #c45c41;">Please fix the following errors:</h3>
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm" style="color: #c45c41;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Session Timeout -->
            <div class="pb-6 border-b border-[var(--border)]">
                <h2 class="text-base font-semibold mb-1" style="color: var(--ink);">Session Timeout</h2>
                <p class="text-xs mb-4" style="color: var(--ink-subtle);">
                    Users are automatically logged out after this duration of inactivity.
                </p>
                <label for="session_timeout" class="block text-sm font-semibold mb-3" style="color: var(--ink);">
                    Session Timeout (Minutes)
                </label>
                <div class="space-y-3">
                    <div>
                        <input type="number" name="session_timeout" id="session_timeout" value="{{ old('session_timeout', $sessionTimeout) }}" min="5" max="2880" class="block w-full border border-[var(--border)] rounded-lg p-3 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                        <p class="text-xs mt-2" style="color: var(--ink-subtle);">Minimum: 5 minutes | Maximum: 2880 minutes (2 days)</p>
                    </div>

                    <!-- Quick presets -->
                    <div class="pt-3 border-t border-[var(--border)]">
                        <p class="text-xs font-medium mb-2" style="color: var(--ink-subtle);">Quick Presets:</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button type="button" class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors duration-200 hover:shadow-sm" style="border-color: var(--border);" onclick="document.getElementById('session_timeout').value = 30; document.getElementById('session_timeout').focus();">
                                30 min
                            </button>
                            <button type="button" class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors duration-200 hover:shadow-sm" style="border-color: var(--border);" onclick="document.getElementById('session_timeout').value = 60; document.getElementById('session_timeout').focus();">
                                1 hour
                            </button>
                            <button type="button" class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors duration-200 hover:shadow-sm" style="border-color: var(--border);" onclick="document.getElementById('session_timeout').value = 120; document.getElementById('session_timeout').focus();">
                                2 hours
                            </button>
                            <button type="button" class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors duration-200 hover:shadow-sm" style="border-color: var(--border);" onclick="document.getElementById('session_timeout').value = 480; document.getElementById('session_timeout').focus();">
                                8 hours
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Lockout -->
            <div class="pb-6 border-b border-[var(--border)]">
                <h2 class="text-base font-semibold mb-1" style="color: var(--ink);">Login Lockout</h2>
                <p class="text-xs mb-4" style="color: var(--ink-subtle);">
                    Accounts are temporarily locked after too many failed login attempts to prevent brute-force attacks.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="login_max_attempts" class="block text-sm font-semibold mb-3" style="color: var(--ink);">
                            Max Failed Attempts
                        </label>
                        <input type="number" name="login_max_attempts" id="login_max_attempts" value="{{ old('login_max_attempts', $loginMaxAttempts) }}" min="1" max="20" class="block w-full border border-[var(--border)] rounded-lg p-3 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                        <p class="text-xs mt-2" style="color: var(--ink-subtle);">Minimum: 1 | Maximum: 20</p>
                    </div>
                    <div>
                        <label for="lockout_duration_minutes" class="block text-sm font-semibold mb-3" style="color: var(--ink);">
                            Lockout Duration (Minutes)
                        </label>
                        <input type="number" name="lockout_duration_minutes" id="lockout_duration_minutes" value="{{ old('lockout_duration_minutes', $lockoutDurationMinutes) }}" min="1" max="1440" class="block w-full border border-[var(--border)] rounded-lg p-3 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                        <p class="text-xs mt-2" style="color: var(--ink-subtle);">Minimum: 1 minute | Maximum: 1440 minutes (1 day)</p>
                    </div>
                </div>
            </div>

            <!-- Password Policy -->
            <div class="pb-6 border-b border-[var(--border)]">
                <h2 class="text-base font-semibold mb-1" style="color: var(--ink);">Password Policy</h2>
                <p class="text-xs mb-4" style="color: var(--ink-subtle);">
                    Applied whenever a password is created, changed, or reset. Users whose current password does not meet the policy are not affected until their next change.
                </p>
                <div>
                    <label for="password_min_length" class="block text-sm font-semibold mb-3" style="color: var(--ink);">
                        Minimum Password Length
                    </label>
                    <input type="number" name="password_min_length" id="password_min_length" value="{{ old('password_min_length', $passwordMinLength) }}" min="8" max="64" class="block w-full border border-[var(--border)] rounded-lg p-3 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                    <p class="text-xs mt-2" style="color: var(--ink-subtle);">Minimum: 8 | Maximum: 64</p>
                </div>

                <div class="mt-4 space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="password_require_uppercase" value="1" @checked($passwordRequireUppercase) class="mt-0.5 w-4 h-4 rounded border border-[var(--border)] focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                        <span class="text-sm" style="color: var(--ink);">Require at least one uppercase letter (A-Z)</span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="password_require_number" value="1" @checked($passwordRequireNumber) class="mt-0.5 w-4 h-4 rounded border border-[var(--border)] focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                        <span class="text-sm" style="color: var(--ink);">Require at least one number (0-9)</span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="password_require_symbol" value="1" @checked($passwordRequireSymbol) class="mt-0.5 w-4 h-4 rounded border border-[var(--border)] focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                        <span class="text-sm" style="color: var(--ink);">Require at least one symbol (!@#$%...)</span>
                    </label>
                </div>
            </div>

            <!-- Info Box -->
            <div class="p-4 rounded-lg" style="background: var(--teal-soft); border: 1px solid rgba(13, 74, 60, 0.2);">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: var(--primary);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--primary);">Security Settings</p>
                        <p class="text-xs mt-1" style="color: var(--primary); opacity: 0.85;">These settings apply to all users in the system. Failed login attempts are tracked per account and automatically reset on successful login.</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4 border-t border-[var(--border)]">
                <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200" style="background: var(--primary); color: white;">
                    Save Settings
                </button>
                <a href="{{ route('profile.show') }}" class="px-6 py-2.5 rounded-lg text-sm font-medium border border-[var(--border)] transition-colors duration-200" style="color: var(--ink);">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection