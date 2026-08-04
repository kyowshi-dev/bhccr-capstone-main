<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoleManagementController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $roles = Role::with('permissions')->orderBy('role_name')->get();

        return view('roles.index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

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

        DB::transaction(function () use ($role, $validated) {
            $role->update(['role_name' => $validated['role_name']]);
            $role->permissions()->sync($validated['permissions'] ?? []);

            // Clear permission caches for every user holding this role.
            foreach ($role->users as $user) {
                Cache::forget("user_permissions_{$user->id}");
            }
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }
}
