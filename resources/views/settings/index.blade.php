@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div>
        <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Settings</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Manage your account and system backups.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-5">
        <a href="{{ route('settings.account') }}"
           class="block p-5 lg:p-6 rounded-xl border transition-all duration-200 hover:shadow-md hover:scale-[1.01]" style="background: var(--bg-surface); border-color: var(--border);">
            <h3 class="font-display font-semibold text-base mb-1" style="color: var(--ink);">Account management</h3>
            <p class="text-sm" style="color: var(--ink-muted);">Change your password and manage your login credentials.</p>
        </a>
        <a href="{{ route('settings.backups') }}"
           class="block p-5 lg:p-6 rounded-xl border transition-all duration-200 hover:shadow-md hover:scale-[1.01]" style="background: var(--bg-surface); border-color: var(--border);">
            <h3 class="font-display font-semibold text-base mb-1" style="color: var(--ink);">Backups</h3>
            <p class="text-sm" style="color: var(--ink-muted);">Export the database for backup or transfer.</p>
        </a>
    </div>
</div>
@endsection
