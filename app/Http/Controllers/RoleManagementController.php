<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoleManagementController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission('users');

        $roles = Role::with('permissions')->orderBy('role_name')->get();

        return view('roles.index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): View
    {
        $this->authorizePermission('users');

        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizePermission('users');

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:255', 'unique:user_roles,role_name,'.$role->id],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Prevent removing the 'users' permission from the Admin role.
        if ($role->role_name === 'Admin' && ! in_array(Permission::where('name', 'users')->value('id'), $validated['permissions'] ?? [], true)) {
            return redirect()
                ->route('roles.edit', $role)
                ->withErrors(['permissions' => 'The Admin role must keep the "User Management" permission.'])
                ->withInput();
        }

        $oldRoleName = $role->role_name;
        $oldPermissions = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->values()->all();

        DB::transaction(function () use ($role, $validated) {
            $role->update(['role_name' => $validated['role_name']]);
            $role->permissions()->sync($validated['permissions'] ?? []);

            // Clear permission caches for every user holding this role.
            foreach ($role->users as $user) {
                Cache::forget("user_permissions_{$user->id}");
            }
        });

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'role_updated',
            'table_name' => 'user_roles',
            'record_id' => $role->id,
            'old_values' => [
                'role_name' => $oldRoleName,
                'permission_ids' => $oldPermissions,
            ],
            'new_values' => [
                'role_name' => $role->role_name,
                'permission_ids' => array_map('intval', $validated['permissions'] ?? []),
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }
}
