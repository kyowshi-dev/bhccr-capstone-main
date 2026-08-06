@extends('layouts.app')

@section('title', 'New User')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl lg:text-2xl font-extrabold" style="color: var(--ink);">Add New User</h1>
            <p class="text-xs lg:text-sm text-ink-muted mt-1">
                Create a new staff account for the system.
            </p>
        </div>

        <a href="{{ route('users.index') }}"
           class="inline-flex items-center px-3 lg:px-4 py-2 rounded-xl border border-border bg-teal-soft text-xs lg:text-sm font-medium text-ink hover:bg-teal-soft transition">
            Back
        </a>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="space-y-4 lg:space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <div>
                <label for="first_name" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    First Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="{{ old('first_name') }}"
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
                    value="{{ old('last_name') }}"
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
                    value="{{ old('username') }}"
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
                    value="{{ old('email') }}"
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
                    value="{{ old('contact_number') }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    placeholder="0912-345-6789"
                >
                @error('contact_number')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Password <span class="text-danger">*</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
                @error('password')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role_id" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Role <span class="text-danger">*</span>
                </label>
                <select
                    id="role_id"
                    name="role_id"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
                    <option value="">Select a role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->role_name }}</option>
                    @endforeach
                </select>
                @error('role_id')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="password_confirmation" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Confirm Password <span class="text-danger">*</span>
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 lg:gap-3 pt-2">
            <a href="{{ route('users.index') }}" class="px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl border border-border text-ink font-medium text-xs lg:text-sm hover:bg-teal-soft">Cancel</a>
            <button
                type="submit"
                class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-xs lg:text-sm font-semibold text-white bg-primary hover:shadow-xl transition"
            >
                Save User
            </button>
        </div>
    </form>
</div>
@endsection
