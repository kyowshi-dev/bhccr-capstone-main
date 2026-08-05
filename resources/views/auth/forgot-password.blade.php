@extends('auth.layout')

@section('title', 'Forgot Password')

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
        <p class="text-xs mt-4 muted-xs leading-relaxed">Pag-enter sa imong username aron makadawat og verification code.</p>
    </div>

    <form action="{{ route('password.forgot.submit') }}" method="POST" id="forgot-form">
        @csrf

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
            <label for="username" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username') }}"
                   class="auth-input" placeholder="Username" required autofocus>
        </div>

        <button type="submit" class="auth-btn">Send verification code</button>
    </form>

    <p class="text-center text-xs mt-5" style="color: var(--ink-muted);">
        <a href="{{ route('login') }}" class="font-medium" style="color: var(--primary); text-decoration: underline;">← Back to sign in</a>
    </p>

    <p class="text-center text-xs mt-6" style="color: var(--ink-muted);">
        &copy; {{ date('Y') }} | Developed by
        <a href="facebook.com/charlz.chavaria" class="font-medium" style="color: var(--primary);">
            PHINMA COC Students
        </a>
    </p>
</div>
@endsection
