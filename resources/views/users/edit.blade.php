@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl lg:text-2xl font-extrabold" style="color: var(--ink);">Edit User</h1>
            <p class="text-xs lg:text-sm text-ink-muted mt-1">
                Update user information and settings.
            </p>
        </div>

        <a href="{{ route('users.index') }}"
           class="inline-flex items-center px-3 lg:px-4 py-2 rounded-xl border border-border bg-surface text-xs lg:text-sm font-medium text-ink hover:bg-teal-soft transition">
            Back
        </a>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4 lg:space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <div>
                <label for="first_name" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    First Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="{{ old('first_name', $healthWorker?->first_name) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
                @error('first_name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="last_name" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Last Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="{{ old('last_name', $healthWorker?->last_name) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
                @error('last_name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Username <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username', $user->username) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
                @error('username')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Email Address <span class="text-danger">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
                @error('email')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="contact_number" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Contact Number
                </label>
                <input
                    type="text"
                    id="contact_number"
                    name="contact_number"
                    value="{{ old('contact_number', $healthWorker?->contact_number) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    placeholder="0912-345-6789"
                >
                @error('contact_number')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    placeholder="Leave blank to keep current password"
                >
                @error('password')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role_id" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Role <span class="text-danger">*</span>
                </label>
                @if ($user->id === auth()->id())
                    <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                    <select
                        id="role_id"
                        name="role_id"
                        disabled
                        class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm text-sm opacity-60 cursor-not-allowed"
                    >
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected($user->role_id == $role->id)>{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-ink-muted">You cannot change your own role.</p>
                @else
                    <select
                        id="role_id"
                        name="role_id"
                        class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                        required
                    >
                        <option value="">Select a role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Confirm Password
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    placeholder="Leave blank to keep current password"
                >
            </div>

            <div id="role-confirm-password" class="hidden md:col-span-2">
                <label for="current_password" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Current Password <span class="text-danger">*</span>
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    autocomplete="current-password"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    placeholder="Enter your password to confirm the role change"
                >
                @error('current_password')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-ink-muted">Role changes require your current password for security.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 lg:gap-3 pt-2">
            <a href="{{ route('users.index') }}" class="px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl border border-border text-ink font-medium text-xs lg:text-sm hover:bg-teal-soft">Cancel</a>
            <button
                type="submit"
                class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-xs lg:text-sm font-semibold text-white bg-primary hover:bg-primary-hover transition shadow-md hover:shadow-xl"
            >
                Update User
            </button>
        </div>
    </form>
</div>

<script>
    const originalRoleId = {{ $user->role_id }};
    const roleSelect = document.getElementById('role_id');
    const confirmBlock = document.getElementById('role-confirm-password');

    function toggleRoleConfirmation() {
        if (!roleSelect || !confirmBlock) {
            return;
        }

        const roleChanged = String(roleSelect.value) !== String(originalRoleId);
        confirmBlock.classList.toggle('hidden', !roleChanged);

        if (!roleChanged) {
            document.getElementById('current_password').value = '';
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleRoleConfirmation);
        toggleRoleConfirmation();
    }
</script>
@endsection
