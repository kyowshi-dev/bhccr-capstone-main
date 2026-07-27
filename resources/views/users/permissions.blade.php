@extends('layouts.app')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-sky-700">Edit Permissions</h1>
            <p class="text-xs lg:text-sm text-gray-600 mt-1">
                Manage permissions for {{ $user->username }}.
            </p>
        </div>

        <a href="{{ route('users.index') }}"
           class="inline-flex items-center justify-center px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl lg:rounded-2xl bg-gray-500 text-xs lg:text-sm font-semibold text-white shadow-md hover:shadow-xl transition">
            Back to Users
        </a>
    </div>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('users.permissions.update', $user) }}" method="POST" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-800">Module Permissions</h2>
                <p class="text-sm text-gray-600">Select the modules this user can access.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($permissions as $permission)
                        <label class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->name }}"
                                {{ $user->permissions->contains('name', $permission->name) ? 'checked' : '' }}
                                class="checkbox h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 rounded">
                            <div>
                                <div class="font-medium text-gray-900">{{ ucfirst($permission->name) }}</div>
                                <div class="text-sm text-gray-500">{{ $permission->description }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2.5 rounded-xl bg-emerald-900 text-sm font-semibold text-white shadow-md hover:bg-emerald-800 hover:shadow-xl transition">
                        Update Permissions
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection