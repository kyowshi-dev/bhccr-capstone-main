@extends('layouts.app')

@section('title', 'Role Manager')

@section('content')
<div class="space-y-4 lg:space-y-6">
    @if (session('success'))
        <div class="p-4 rounded-xl bg-teal-soft border border-primary/20 text-primary">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-check-circle mt-1"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-xl bg-danger-soft border border-danger/30 text-danger">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-exclamation-circle mt-1"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-danger-soft border border-danger/30 text-danger">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-exclamation-circle mt-1"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div>
        <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Role Manager</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Assign permissions to roles. Users inherit permissions from their role.</p>
    </div>

    <div class="overflow-hidden rounded-xl lg:rounded-2xl border bg-surface shadow-sm" style="border-color: var(--border);">
        <table class="min-w-full divide-y divide-border">
            <thead class="bg-teal-soft/60">
                <tr>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted whitespace-nowrap">Role</th>
                    <th class="px-3 lg:px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink-muted">Permissions</th>
                    <th class="px-3 lg:px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-ink-muted whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-surface divide-y divide-border">
                @forelse ($roles as $role)
                    <tr class="hover:bg-black/[0.03] transition-colors">
                        <td class="px-3 lg:px-6 py-3 text-sm font-medium whitespace-nowrap" style="color: var(--ink);">
                            {{ $role->role_name }}
                        </td>
                        <td class="px-3 lg:px-6 py-3 text-sm" style="color: var(--ink-muted);">
                            @if ($role->permissions->isEmpty())
                                <span class="text-xs">No permissions assigned</span>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($role->permissions as $permission)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border" style="border-color: var(--border); background: var(--bg-surface-elevated); color: var(--ink-muted);">
                                            {{ $permission->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-3 lg:px-6 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('roles.edit', $role) }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-full border text-xs font-semibold transition hover:bg-primary/10"
                               style="border-color: var(--primary); color: var(--primary);">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center">
                            <div class="flex justify-center mb-3"><i class="fa-solid fa-user-shield text-3xl" style="color: var(--ink-subtle);"></i></div>
                            <p class="text-sm font-medium" style="color: var(--ink);">No roles found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection