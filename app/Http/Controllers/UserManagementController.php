<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $pageSize = auth()->check() && auth()->user()->isAdmin() ? 10 : 15;

        $users = User::with('permissions')->orderBy('username')->paginate($pageSize);
        $permissions = Permission::all();

        return view('users.index', [
            'users' => $users,
            'permissions' => $permissions,
        ]);
    }

    public function create()
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        return view('users.create');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+\-\s()]*$/'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            DB::table('health_workers')->insert([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'role' => 'User',
                'contact_number' => $validated['contact_number'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $healthWorker = DB::table('health_workers')->where('user_id', $user->id)->first();

        return view('users.edit', [
            'user' => $user,
            'healthWorker' => $healthWorker,
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+\-\s()]*$/'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            if ($user->username !== $validated['username']) {
                $user->username = $validated['username'];
            }

            if ($user->email !== $validated['email']) {
                $user->email = $validated['email'];
            }

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            DB::table('health_workers')
                ->where('user_id', $user->id)
                ->update([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'role' => 'User',
                    'contact_number' => $validated['contact_number'] ?? null,
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function disable(User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        if ($user->isAdmin()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Admin accounts cannot be disabled.');
        }

        if (! $user->is_active) {
            return redirect()
                ->route('users.index')
                ->with('success', 'User is already disabled.');
        }

        $user->is_active = false;
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User disabled successfully.');
    }

    public function enable(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        // Validate password confirmation
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        if ($user->is_active) {
            return redirect()
                ->route('users.index')
                ->with('success', 'User is already enabled.');
        }

        $user->is_active = true;
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User enabled successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Validate password confirmation
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // Delete the user (this will cascade delete health_worker due to foreign key constraint)
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function editPermissions(User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $permissions = Permission::all();

        return view('users.permissions', [
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    public function getPermissionsData(User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $authUser = auth()->user();
        $isAdminEditingSelf = $authUser->id === $user->id && $authUser->isAdmin();

        return response()->json([
            'permissions' => $user->permissions->pluck('name')->toArray(),
            'isAdminEditingSelf' => $isAdminEditingSelf,
        ]);
    }

    public function updatePermissions(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        // Prevent admin from removing the 'users' permission from themselves
        $authUser = auth()->user();
        if ($authUser->id === $user->id && $authUser->isAdmin()) {
            $requestedPermissions = $request->permissions ?? [];
            if (! in_array('users', $requestedPermissions)) {
                return redirect()
                    ->route('users.index')
                    ->with('error', 'You cannot remove the "User Management" permission from your own account. This would lock you out of the system.')
                    ->with('warning', true);
            }
        }

        $user->permissions()->sync(Permission::whereIn('name', $request->permissions ?? [])->pluck('id'));

        // Clear cache
        Cache::forget("user_permissions_{$user->id}");

        return redirect()
            ->route('users.index')
            ->with('success', 'User permissions updated successfully.');
    }

    public function passwordResetRequests()
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $requests = PasswordResetRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('users.password-reset-requests', [
            'requests' => $requests,
        ]);
    }

    public function completePasswordResetRequest(Request $request, PasswordResetRequest $passwordResetRequest)
    {
        $passwordResetRequest->update([
            'status' => 'completed',
            'admin_note' => $request->input('admin_note'),
            'completed_at' => now(),
        ]);

        return redirect()->route('users.password-reset-requests')->with('success', 'Password reset request marked as completed.');
    }
}
