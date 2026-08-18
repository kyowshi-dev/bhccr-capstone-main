@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Edit Role</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Update permissions for the <strong>{{ $role->role_name }}</strong> role.</p>
        </div>

        <a href="{{ route('roles.index') }}"
           class="inline-flex items-center px-3 lg:px-4 py-2 rounded-xl border border-border bg-surface text-xs lg:text-sm font-medium text-ink hover:bg-teal-soft transition">
            Back
        </a>
    </div>

    <form action="{{ route('roles.update', $role) }}" method="POST" class="space-y-4 lg:space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <div>
                <label for="role_name" class="block text-xs lg:text-sm font-medium text-ink mb-1">
                    Role Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="role_name"
                    name="role_name"
                    value="{{ old('role_name', $role->role_name) }}"
                    class="block w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border shadow-sm focus:border-accent-blue focus:ring-accent-blue text-sm"
                    required
                >
                @error('role_name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold" style="color: var(--ink);">Module Permissions</h3>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="selectAllPermissions" class="h-4 w-4 rounded border-border focus:ring-accent-blue">
                    <span class="text-sm" style="color: var(--ink-muted);">Select All</span>
                </label>
            </div>
            <p class="text-sm mt-1 mb-3" style="color: var(--ink-muted);">Users assigned to this role inherit these permissions.</p>

            <div id="permissionsList" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($permissions as $permission)
                    <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors hover:bg-black/[0.03]"
                           style="border-color: var(--border); background: var(--bg-surface-elevated);">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->id }}"
                            class="permission-checkbox h-4 w-4 mt-0.5 rounded border-border focus:ring-accent-blue"
                            @checked(in_array($permission->id, old('permissions', $role->permissions->pluck('id')->all()), true))
                            @disabled($role->role_name === 'Admin' && $permission->name === 'users')
                            data-name="{{ $permission->name }}"
                        >
                        <div class="min-w-0">
                            <div class="font-medium text-sm" style="color: var(--ink);">{{ ucfirst($permission->name) }}</div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ $permission->description }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('permissions')
                <p class="mt-2 text-xs text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 lg:gap-3 pt-2">
            <a href="{{ route('roles.index') }}" class="px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl border border-border text-ink font-medium text-xs lg:text-sm hover:bg-teal-soft">Cancel</a>
            <button type="submit" class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-xs lg:text-sm font-semibold text-white bg-primary hover:bg-primary-hover transition shadow-md hover:shadow-xl">
                Update Role
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAllPermissions');
        const checkboxes = document.querySelectorAll('.permission-checkbox');

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    if (!checkbox.disabled) {
                        checkbox.checked = selectAll.checked;
                    }
                });
                updateSelectAll();
            });
        }

        function updateSelectAll() {
            const enabled = Array.from(checkboxes).filter(function (c) { return !c.disabled; });
            const checkedCount = enabled.filter(function (c) { return c.checked; }).length;
            selectAll.checked = enabled.length > 0 && enabled.length === checkedCount;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < enabled.length;
        }

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('permission-checkbox')) {
                updateSelectAll();
            }
        });

        updateSelectAll();
    });
</script>
@endsection