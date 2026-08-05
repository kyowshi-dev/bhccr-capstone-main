@extends('auth.layout')

@section('title', 'Verify Password Reset')

@section('content')
<div class="auth-card animate-in opacity-0">
    <div class="text-center mb-6">
        <div class="flex items-center justify-center gap-3">
            <div class="logo-mark" style="width: 56px; height: 56px; border-radius: 14px;">
                <img src="{{ asset('img/logo.svg') }}" alt="Santa Ana logo">
            </div>
            <div class="text-left">
                <h1 class="font-extrabold auth-title leading-snug mb-0">Barangay Health Center Information System</h1>
                <p class="muted-xs leading-tight">Sta. Ana Health Center</p>
            </div>
        </div>
        <p class="text-xs mt-4 muted-xs leading-relaxed">Isulod ang verification code ug ang imong bag-ong password.</p>
    </div>

    <form action="{{ route('password.forgot.verify.submit') }}" method="POST" id="verify-form">
        @csrf
        <input type="hidden" name="username" value="{{ $username }}">

        @if (session('status'))
            <div class="mb-4 p-3 text-sm border-l-4 bg-teal-soft" style="border-left-color: var(--primary); color: var(--primary);">
                <p class="font-medium text-sm">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 text-sm border-l-4 bg-danger-soft" style="border-left-color: var(--danger); color: var(--danger);">
                <p class="font-medium text-sm">{{ $errors->first() }}</p>
            </div>
        @endif

        <div class="mb-4">
            <label for="otp" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Verification code</label>
            <input type="text" name="otp" id="otp" value="{{ old('otp') }}"
                   class="auth-input tracking-[0.3em]" placeholder="123456" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">Check the email registered to your account. The code expires in 15 minutes.</p>
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">New password</label>
            <input type="password" name="password" id="password"
                   class="auth-input" placeholder="At least 8 characters" minlength="8" required>
        </div>

        <div class="mb-5">
            <label for="password_confirmation" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Confirm new password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="auth-input" placeholder="Repeat your new password" minlength="8" required>
        </div>

        <button type="submit" class="auth-btn">Reset password</button>
    </form>

    <p class="text-center text-xs mt-5" style="color: var(--ink-muted);">
        <a href="{{ route('password.forgot') }}" class="font-medium" style="color: var(--primary); text-decoration: underline;">← Request a new code</a>
    </p>

    <p class="text-center text-xs mt-6" style="color: var(--ink-muted);">
        &copy; {{ date('Y') }} | Developed by
        <a href="facebook.com/charlz.chavaria" class="font-medium" style="color: var(--primary);">
            PHINMA COC Students
        </a>
    </p>
</div>
@endsection
