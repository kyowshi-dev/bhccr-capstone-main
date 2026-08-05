@extends('auth.layout')

@section('title', 'Login')

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
        <p class="text-xs mt-4 muted-xs leading-relaxed">Pag-log in aron maka-access sa mga record sa pasyente ug mga serbisyo.</p>
    </div>

    <form action="{{ route('login.process') }}" method="POST" id="login-form">
        <input type="hidden" name="_token" id="csrf-token-input" value="{{ csrf_token() }}" autocomplete="off">

        @if (session('error') || session('session_expired'))
            <div class="mb-4 p-3 text-sm border-l-4 bg-danger-soft" style="border-left-color: var(--danger); color: var(--danger);">
                <p class="font-medium text-sm">{{ session('error', 'Your session has expired. Please sign in again.') }}</p>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-3 text-sm border-l-4 bg-teal-soft" style="border-left-color: var(--primary); color: var(--primary);">
                <p class="font-medium text-sm">{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 text-sm border-l-4 bg-danger-soft" style="border-left-color: var(--danger); color: var(--danger);">
                <p class="font-medium text-sm">Login failed. {{ $errors->first() }}</p>
            </div>
        @endif

        <div class="mb-4">
            <label for="username" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username') }}"
                   class="auth-input" placeholder="Username" required autofocus>
        </div>

        <div class="mb-5">
            <label for="password" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Password</label>
            <input type="password" name="password" id="password"
                   class="auth-input" placeholder="Password" required>
        </div>

        <div class="flex items-center justify-between mb-5 text-sm">
            <label class="flex items-center text-sm cursor-pointer" style="color: var(--ink-muted);">
                <input type="checkbox" name="remember" class="mr-2 h-4 w-4 rounded" style="accent-color: var(--primary);">
                Remember me
            </label>
            <a href="{{ route('password.forgot') }}" class="text-sm font-medium" style="color: var(--primary); text-decoration: underline;">
                Forgot Password?
            </a>
        </div>

        <button type="submit" class="auth-btn">Sign in</button>
    </form>

    <p class="text-center text-xs mt-6" style="color: var(--ink-muted);">
        &copy; {{ date('Y') }} | Developed by
        <a href="facebook.com/charlz.chavaria" class="font-medium" style="color: var(--primary);">
            PHINMA COC Students
        </a>
    </p>
</div>
@endsection
