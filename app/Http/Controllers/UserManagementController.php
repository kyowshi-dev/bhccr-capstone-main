<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission('users');

        $pageSize = auth()->user()->isAdmin() ? 10 : 15;

        $users = User::with('role')->orderBy('username')->paginate($pageSize);

        return view('users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission('users');

        return view('users.create', [
            'roles' => Role::orderBy('role_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('users');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+\-\s()]*$/'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:user_roles,id'],
        ]);

        UserManagementService::create($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorizePermission('users');

        $healthWorker = DB::table('health_workers')->where('user_id', $user->id)->first();

        return view('users.edit', [
            'user' => $user,
            'healthWorker' => $healthWorker,
            'roles' => Role::orderBy('role_name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizePermission('users');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+\-\s()]*$/'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:user_roles,id'],
        ]);

        UserManagementService::update($user, $validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function disable(User $user): RedirectResponse
    {
        $this->authorizePermission('users');

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

    public function enable(Request $request, User $user): RedirectResponse
    {
        $this->authorizePermission('users');

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

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizePermission('users');

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
}
