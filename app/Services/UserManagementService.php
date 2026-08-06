<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UserManagementService
{
    public static function create(array $validated): void
    {
        $role = Role::findOrFail($validated['role_id']);

        DB::transaction(function () use ($validated, $role) {
            $user = User::query()->create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'role_id' => $role->id,
            ]);

            DB::table('health_workers')->insert([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'role' => $role->role_name,
                'contact_number' => $validated['contact_number'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public static function update(User $user, array $validated): void
    {
        $role = Role::findOrFail($validated['role_id']);

        DB::transaction(function () use ($user, $validated, $role) {
            if ($user->username !== $validated['username']) {
                $user->username = $validated['username'];
            }

            if ($user->email !== $validated['email']) {
                $user->email = $validated['email'];
            }

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->role_id = $role->id;
            $user->save();

            Cache::forget("user_permissions_{$user->id}");

            DB::table('health_workers')
                ->where('user_id', $user->id)
                ->update([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'role' => $role->role_name,
                    'contact_number' => $validated['contact_number'] ?? null,
                    'updated_at' => now(),
                ]);
        });
    }
}
