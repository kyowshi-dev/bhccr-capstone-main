<?php

namespace App\Http\Controllers;

use App\Http\Requests\RestrictUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
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

        $pageSize = pageSize(auth()->user()->isAdmin() ? 10 : 15);

        $users = User::with('role')->orderBy('username')->paginate($pageSize)->withQueryString();

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

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorizePermission('users');

        UserManagementService::create($request->validated());

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

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizePermission('users');

        $validated = $request->validated();

        // Admins cannot change their own role.
        if ($user->id === auth()->id()) {
            $validated['role_id'] = $user->role_id;
        }

        UserManagementService::update($user, $validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function disable(RestrictUserRequest $request, User $user): RedirectResponse
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

    public function enable(RestrictUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizePermission('users');

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

    public function destroy(RestrictUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizePermission('users');

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
