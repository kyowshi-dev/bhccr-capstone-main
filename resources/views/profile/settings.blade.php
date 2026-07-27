@extends('layouts.app')

@section('title', 'Session Settings')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Back Button -->
    <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-2 text-sm font-medium" style="color: var(--primary);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Profile
    </a>

    <!-- Session Settings Form -->
    <div class="rounded-2xl p-6 border border-[var(--border)]" style="background: var(--bg-surface-elevated); box-shadow: var(--shadow-sm);">
        <h1 class="text-2xl font-display font-semibold mb-2" style="color: var(--ink);">Session Settings</h1>
        <p class="text-sm mb-6" style="color: var(--ink-subtle);">
            Configure session timeout for all users in the system. Users will be automatically logged out after the specified duration of inactivity.
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
            <div>
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

            <!-- Info Box -->
            <div class="p-4 rounded-lg" style="background: var(--teal-soft); border: 1px solid rgba(13, 74, 60, 0.2);">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: var(--primary);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium" style="color: var(--primary);">Session Management</p>
                        <p class="text-xs mt-1" style="color: var(--primary); opacity: 0.85;">Users will be logged out automatically after the configured period of inactivity. This setting applies to all users in the system.</p>
                    </div>
                </div>
            </div>

            <!-- Current Setting -->
            <div class="p-4 rounded-lg border border-[var(--border)]" style="background: var(--bg-page);">
                <p class="text-xs font-medium uppercase tracking-wider" style="color: var(--ink-subtle);">Current Setting</p>
                <p class="text-lg font-semibold mt-2" style="color: var(--ink);">
                    {{ (int) $sessionTimeout }} minutes
                    <span class="text-sm font-normal" style="color: var(--ink-subtle);">
                        ({{ floor($sessionTimeout / 60) }}h {{ $sessionTimeout % 60 }}m)
                    </span>
                </p>
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
