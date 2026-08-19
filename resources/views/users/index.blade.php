@extends('layouts.app')

@section('title', 'User Management')

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

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">User Management</h1>
            <p class="text-xs lg:text-sm text-ink-muted mt-1">
                View and manage all registered users in the system.
            </p>
        </div>

        <a href="{{ route('users.create') }}"
           class="inline-flex items-center justify-center px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl lg:rounded-2xl bg-[var(--primary)] text-xs lg:text-sm font-semibold text-white shadow-md hover:bg-[var(--primary-light)] transition">
            + Add User
</a>
    </div>

    <div class="overflow-hidden rounded-xl lg:rounded-2xl border border-border bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-teal-soft">
                    <tr>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Username</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Email</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Role</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Registered At</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-right text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-surface divide-y divide-border">
                    @forelse ($users as $user)
                        <tr class="hover:bg-black/5 transition-colors">
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink">
                                <div class="font-medium">{{ $user->username }}</div>
                                <div class="text-xs text-ink-muted md:hidden">{{ $user->email }}</div>
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted hidden md:table-cell">
                                {{ $user->email }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink">
                                <span class="inline-flex items-center px-2 lg:px-3 py-0.5 lg:py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                                    {{ $user->role?->role_name ?? 'No role' }}
                                </span>
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted hidden lg:table-cell">
                                {{ $user->created_at?->format('M d, Y') }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center px-2 lg:px-3 py-0.5 lg:py-1 rounded-full text-xs font-semibold bg-teal-soft text-primary">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 lg:px-3 py-0.5 lg:py-1 rounded-full text-xs font-semibold bg-teal-soft text-ink">
                                        Disabled
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border text-xs font-semibold transition hover:bg-primary/10" style="border-color: var(--primary); color: var(--primary);">
                                        Edit
                                    </a>
                                    @if ($user->is_active && ! $user->isAdmin())
                                        <button
                                            type="button"
                                            onclick="confirmDisableUser({{ $user->id }})"
                                            class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border border-danger/30 text-xs font-semibold text-danger hover:bg-danger-soft transition"
                                        >
                                            Disable
                                        </button>
                                    @endif
                                    @if (! $user->is_active)
                                        <button
                                            type="button"
                                            onclick="confirmEnableUser({{ $user->id }})"
                                            class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border border-emerald-300 text-xs font-semibold text-primary hover:bg-teal-soft transition"
                                        >
                                            Enable
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex justify-center mb-3"><i class="fa-solid fa-users text-3xl" style="color: var(--ink-subtle);"></i></div>
                                <p class="text-sm font-medium" style="color: var(--ink);">No users found</p>
                                <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Start by adding a user to manage system access</p>
                                <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus"></i> Add first user</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <x-pagination :paginator="$users" />
    </div>
</div>

<!-- Hidden Forms for Actions -->
<form id="disableForm" method="POST" style="display: none;">
    @csrf
</form>

<form id="enableForm" method="POST" style="display: none;">
    @csrf
</form>

<script>
    function confirmDisableUser(userId) {
        Swal.fire({
            title: 'Disable User?',
            text: 'Are you sure you want to disable this user? They will no longer be able to access the system.',
            icon: 'warning',
            input: 'password',
            inputLabel: 'Your Password',
            inputPlaceholder: 'Enter your password',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonColor: 'var(--danger)',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Disable',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter your password';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('disableForm');
                form.action = '/users/' + userId + '/disable';

                const passwordInput = document.createElement('input');
                passwordInput.type = 'hidden';
                passwordInput.name = 'current_password';
                passwordInput.value = result.value;

                const existingPassword = form.querySelector('input[name="current_password"]');
                if (existingPassword) {
                    existingPassword.remove();
                }

                form.appendChild(passwordInput);
                form.submit();
            }
        });
    }

    function confirmEnableUser(userId) {
        Swal.fire({
            title: 'Enable User?',
            text: 'Enter your password to enable this user.',
            icon: 'info',
            input: 'password',
            inputLabel: 'Your Password',
            inputPlaceholder: 'Enter your password',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonColor: 'var(--primary)',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Enable User',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter your password';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('enableForm');
                form.action = '/users/' + userId + '/enable';
                
                // Add password field to the form
                const passwordInput = document.createElement('input');
                passwordInput.type = 'hidden';
                passwordInput.name = 'current_password';
                passwordInput.value = result.value;
                
                // Clear any existing password inputs
                const existingPassword = form.querySelector('input[name="current_password"]');
                if (existingPassword) {
                    existingPassword.remove();
                }
                
                form.appendChild(passwordInput);
                form.submit();
            }
        });
    }
</script>
@endsection
