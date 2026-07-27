@extends('layouts.app')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl lg:text-2xl font-extrabold text-sky-700">Edit User</h1>
            <p class="text-xs lg:text-sm text-gray-600 mt-1">
                Update user information and settings.
            </p>
        </div>

        <a href="{{ route('users.index') }}"
           class="inline-flex items-center px-3 lg:px-4 py-2 rounded-xl border border-gray-300 bg-white text-xs lg:text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            ← Back
        </a>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4 lg:space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <div>
                <label for="first_name" class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                    First Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="{{ old('first_name', $healthWorker?->first_name) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
                    required
                >
                @error('first_name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="last_name" class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                    Last Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="{{ old('last_name', $healthWorker?->last_name) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
                    required
                >
                @error('last_name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                    Username <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username', $user->username) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
                    required
                >
                @error('username')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
                    required
                >
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="contact_number" class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                    Contact Number
                </label>
                <input
                    type="text"
                    id="contact_number"
                    name="contact_number"
                    value="{{ old('contact_number', $healthWorker?->contact_number) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
                    placeholder="0912-345-6789"
                >
                @error('contact_number')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
                    placeholder="Leave blank to keep current password"
                >
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs lg:text-sm font-medium text-gray-700 mb-1">
                    Confirm Password
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
                    placeholder="Leave blank to keep current password"
                >
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 lg:gap-3 pt-2">
            <a href="{{ route('users.index') }}" class="px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl border border-gray-300 text-gray-700 font-medium text-xs lg:text-sm hover:bg-gray-50">Cancel</a>
            <button
                type="submit"
                class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-xs lg:text-sm font-semibold text-white bg-emerald-900 hover:bg-emerald-800 transition shadow-md hover:shadow-xl"
            >
                Update User
            </button>
        </div>
    </form>
</div>
@endsection
