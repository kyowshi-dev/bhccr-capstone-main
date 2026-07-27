@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="space-y-6">
    <!-- Profile Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-[var(--border)]">
        <div class="flex items-start gap-4">
            <div class="h-20 w-20 rounded-full flex items-center justify-center text-2xl font-semibold flex-shrink-0"
                 style="background: var(--teal-soft); color: var(--primary);">
                @if($user->profile_photo_path)
                    <img src="{{ Storage::url($user->profile_photo_path) }}" alt="{{ $user->username }}" class="h-20 w-20 rounded-full object-cover">
                @else
                    {{ mb_strtoupper(mb_substr($user->username, 0, 1)) }}
                @endif
            </div>
            <div>
                <h1 class="text-2xl font-display font-semibold" style="color: var(--ink);">
                    {{ $user->username }}
                </h1>
                <p class="text-sm mt-1" style="color: var(--ink-subtle);">
                    @php
                        $healthWorker = \Illuminate\Support\Facades\DB::table('health_workers')
                            ->where('user_id', $user->id)
                            ->first();
                    @endphp
                    @if($healthWorker)
                        {{ ucfirst($healthWorker->role ?? 'Staff Member') }}
                    @else
                        Health Center Staff
                    @endif
                </p>
                @if($user->email)
                    <p class="text-sm mt-1" style="color: var(--ink-muted);">
                        {{ $user->email }}
                    </p>
                @endif
            </div>
        </div>
        <a href="{{ route('profile.edit') }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200"
           style="background: var(--primary); color: white;">
            Edit Profile
        </a>
    </div>

    <!-- Bio Section -->
    @if($user->bio)
        <div class="space-y-2">
            <h2 class="font-semibold" style="color: var(--ink);">About</h2>
            <p class="text-sm leading-relaxed" style="color: var(--ink-muted);">
                {{ $user->bio }}
            </p>
        </div>
    @endif

    <!-- Account Info -->
    <div class="space-y-4">
        <h2 class="font-semibold" style="color: var(--ink);">Account Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-lg border border-[var(--border)]" style="background: var(--bg-page);">
                <p class="text-xs font-medium uppercase tracking-wider" style="color: var(--ink-subtle);">Username</p>
                <p class="text-sm font-medium mt-1" style="color: var(--ink);">{{ $user->username }}</p>
            </div>
            <div class="p-4 rounded-lg border border-[var(--border)]" style="background: var(--bg-page);">
                <p class="text-xs font-medium uppercase tracking-wider" style="color: var(--ink-subtle);">Email</p>
                <p class="text-sm font-medium mt-1" style="color: var(--ink);">{{ $user->email ?? 'Not set' }}</p>
            </div>
            <div class="p-4 rounded-lg border border-[var(--border)]" style="background: var(--bg-page);">
                <p class="text-xs font-medium uppercase tracking-wider" style="color: var(--ink-subtle);">Status</p>
                <p class="text-sm font-medium mt-1">
                    @if($user->is_active)
                        <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: rgba(13, 74, 60, 0.1); color: var(--primary);">
                            Active
                        </span>
                    @else
                        <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: rgba(196, 92, 65, 0.1); color: #c45c41;">
                            Inactive
                        </span>
                    @endif
                </p>
            </div>
            <div class="p-4 rounded-lg border border-[var(--border)]" style="background: var(--bg-page);">
                <p class="text-xs font-medium uppercase tracking-wider" style="color: var(--ink-subtle);">Member Since</p>
                <p class="text-sm font-medium mt-1" style="color: var(--ink);">{{ $user->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Permissions -->
    <div class="space-y-4">
        <h2 class="font-semibold" style="color: var(--ink);">Permissions</h2>
        <div class="p-4 rounded-lg border border-[var(--border)]" style="background: var(--bg-page);">
            @if($user->permissions->count() > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($user->permissions as $permission)
                        <span class="px-3 py-1 rounded-full text-xs font-medium" style="background: var(--teal-soft); color: var(--primary);">
                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                        </span>
                    @endforeach
                </div>
            @else
                <p class="text-sm" style="color: var(--ink-subtle);">No permissions assigned</p>
            @endif
        </div>
    </div>
</div>
@endsection
