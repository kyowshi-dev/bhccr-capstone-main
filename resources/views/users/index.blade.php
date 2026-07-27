@extends('layouts.app')

@section('content')
<div class="space-y-4 lg:space-y-6">
    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-check-circle mt-1"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-exclamation-circle mt-1"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-sky-700">User Management</h1>
            <p class="text-xs lg:text-sm text-gray-600 mt-1">
                View and manage all registered users in the system.
            </p>
        </div>

        <a href="{{ route('users.create') }}"
           class="inline-flex items-center justify-center px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl lg:rounded-2xl bg-[var(--primary)] text-xs lg:text-sm font-semibold text-white shadow-md hover:bg-[var(--primary-light)] transition">
            + Add User
</a>
    </div>

    <div class="overflow-hidden rounded-xl lg:rounded-2xl border border-gray-200 bg-white/80 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Username</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Email</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Registered At</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-sky-50/60 transition-colors">
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-gray-700">
                                <div class="font-medium">{{ $user->username }}</div>
                                <div class="text-xs text-gray-500 md:hidden">{{ $user->email }}</div>
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-gray-500 hidden md:table-cell">
                                {{ $user->email }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-gray-500 hidden lg:table-cell">
                                {{ $user->created_at?->format('M d, Y') }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center px-2 lg:px-3 py-0.5 lg:py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 lg:px-3 py-0.5 lg:py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700">
                                        Disabled
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border border-sky-300 text-xs font-semibold text-sky-600 hover:bg-sky-50 transition">
                                        Edit
                                    </a>
                                    <button
                                        type="button"
                                        onclick="openPermissionsModal({{ $user->id }}, '{{ $user->username }}')"
                                        class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border border-purple-300 text-xs font-semibold text-purple-600 hover:bg-purple-50 transition"
                                    >
                                        Permissions
                                    </button>
                                    @if ($user->is_active && ! $user->isAdmin())
                                        <button
                                            type="button"
                                            onclick="confirmDisableUser({{ $user->id }})"
                                            class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border border-red-300 text-xs font-semibold text-red-600 hover:bg-red-50 transition"
                                        >
                                            Disable
                                        </button>
                                    @endif
                                    @if (! $user->is_active)
                                        <button
                                            type="button"
                                            onclick="confirmEnableUser({{ $user->id }})"
                                            class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border border-emerald-300 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 transition"
                                        >
                                            Enable
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
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
        {{ $users->links() }}
    </div>
</div>

<!-- Hidden Forms for Actions -->
<form id="disableForm" method="POST" style="display: none;">
    @csrf
</form>

<form id="enableForm" method="POST" style="display: none;">
    @csrf
</form>

@push('modal-content')
<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Edit Permissions</h2>
        <button onclick="closePageDrawer()" class="text-gray-400 hover:text-gray-600">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <p class="text-sm text-gray-600 mb-4">Manage permissions for <span id="modalUsername" class="font-medium"></span>.</p>

    <form id="permissionsForm" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-medium text-gray-800">Module Permissions</h3>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="selectAllPermissions" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                    <span class="text-sm text-gray-700">Select All</span>
                </label>
            </div>
            <p class="text-sm text-gray-600">Select the modules this user can access.</p>

            <div id="permissionsList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($permissions as $permission)
                    <label class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox"
                               name="permissions[]"
                               value="{{ $permission->name }}"
                               class="permission-checkbox h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                        <div>
                            <div class="font-medium text-gray-900">{{ ucfirst($permission->name) }}</div>
                            <div class="text-sm text-gray-500">{{ $permission->description }}</div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closePageDrawer()" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-medium text-sm hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-6 py-2 rounded-xl bg-emerald-900 text-white font-semibold text-sm shadow-md hover:bg-emerald-800 hover:shadow-xl transition">
                    Update Permissions
                </button>
            </div>
        </div>
    </form>
</div>
@endpush

<script>
    function confirmDisableUser(userId) {
        Swal.fire({
            title: 'Disable User?',
            text: 'Are you sure you want to disable this user? They will no longer be able to access the system.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Disable',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('disableForm');
                form.action = '/users/' + userId + '/disable';
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
                passwordInput.name = 'password';
                passwordInput.value = result.value;
                
                // Clear any existing password inputs
                const existingPassword = form.querySelector('input[name="password"]');
                if (existingPassword) {
                    existingPassword.remove();
                }
                
                form.appendChild(passwordInput);
                form.submit();
            }
        });
    }

    if (typeof window.openPageDrawer !== 'function') {
        window.openPageDrawer = function() {
            const modal = document.getElementById('pageModal');
            const panel = document.getElementById('pageModalPanel');
            if (!modal || !panel) {
                return;
            }
            modal.classList.remove('hidden');
            requestAnimationFrame(() => {
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            });
        };
    }

    if (typeof window.closePageDrawer !== 'function') {
        window.closePageDrawer = function() {
            const modal = document.getElementById('pageModal');
            const panel = document.getElementById('pageModalPanel');
            if (!modal || !panel) {
                return;
            }
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');
            panel.addEventListener('transitionend', function handleTransitionEnd() {
                modal.classList.add('hidden');
                panel.removeEventListener('transitionend', handleTransitionEnd);
            }, { once: true });
        };
    }

    function openPermissionsModal(userId, username) {
        document.getElementById('modalUsername').textContent = username;
        document.getElementById('permissionsForm').action = '/users/' + userId + '/permissions';
        
        // Fetch current user permissions via AJAX
        fetch('/users/' + userId + '/permissions-data')
            .then(response => response.json())
            .then(data => {
                // Reset all checkboxes
                document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.disabled = false;
                });
                
                // Check the permissions the user currently has
                data.permissions.forEach(permissionName => {
                    const checkbox = document.querySelector(`input[name="permissions[]"][value="${permissionName}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
                
                // If admin is editing themselves, disable the 'users' permission checkbox
                if (data.isAdminEditingSelf) {
                    const usersCheckbox = document.querySelector(`input[name="permissions[]"][value="users"]`);
                    if (usersCheckbox) {
                        usersCheckbox.disabled = true;
                        usersCheckbox.checked = true;
                        // Add a visual indicator
                        const label = usersCheckbox.closest('label');
                        if (label) {
                            label.classList.add('opacity-60', 'cursor-not-allowed');
                            label.title = 'You cannot remove this permission from your own account';
                        }
                    }
                }
                
                // Update select all checkbox
                updateSelectAllCheckbox();
                
                // Show modal
                openPageDrawer();
            })
            .catch(error => {
                console.error('Error fetching permissions:', error);
                Swal.fire('Error', 'Failed to load permissions data.', 'error');
            });
    }

    function updateSelectAllCheckbox() {
        const checkboxes = document.querySelectorAll('.permission-checkbox');
        const checkedBoxes = document.querySelectorAll('.permission-checkbox:checked');
        const selectAllCheckbox = document.getElementById('selectAllPermissions');
        
        selectAllCheckbox.checked = checkboxes.length === checkedBoxes.length && checkboxes.length > 0;
        selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
    }

    // Initialize modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all functionality
        document.getElementById('selectAllPermissions').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                // Don't uncheck disabled checkboxes (like 'users' when admin edits self)
                if (!checkbox.disabled) {
                    checkbox.checked = isChecked;
                }
            });
            updateSelectAllCheckbox();
        });

        // Update select all when individual checkboxes change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('permission-checkbox')) {
                // Prevent unchecking disabled checkboxes
                if (e.target.disabled && !e.target.checked) {
                    e.target.checked = true;
                    return;
                }
                updateSelectAllCheckbox();
            }
        });

        // Close modal when clicking outside
        const pageModal = document.getElementById('pageModal');
        if (pageModal) {
            pageModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closePageDrawer();
                }
            });
        }
    });
</script>
@endsection
