@extends('layouts.app')

@section('title', 'Account Settings')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Account management</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Change your password and manage your login credentials.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="text-sm font-medium transition hover:underline" style="color: var(--primary);">← Back to Settings</a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--teal-soft); border-color: var(--primary); color: var(--primary);">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl border p-5 lg:p-6 max-w-md" style="background: var(--bg-surface); border-color: var(--border);">
        <p class="text-sm mb-4" style="color: var(--ink-muted);">Logged in as <strong style="color: var(--ink);">{{ $user->username }}</strong> ({{ $user->email }}).</p>
        <h2 class="text-sm font-semibold mb-3" style="color: var(--ink);">Change password</h2>
        <form action="{{ route('settings.account.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="current_password" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Current password</label>
                <input type="password" id="current_password" name="current_password" required
                       class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                       autocomplete="current-password">
                @error('current_password')
                    <p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">New password</label>
                <input type="password" id="password" name="password" required
                       class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                       autocomplete="new-password">
                @error('password')
                    <p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                       autocomplete="new-password">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--accent);">
                Update password
            </button>
        </form>
    </div>
</div>
@endsection
