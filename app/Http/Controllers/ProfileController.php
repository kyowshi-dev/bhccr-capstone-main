<?php

namespace App\Http\Controllers;

use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show', [
            'user' => Auth::user(),
        ]);
    }

    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'max:5120'], // 5MB max
        ], [
            'profile_photo.image' => 'The profile photo must be an image file.',
            'profile_photo.max' => 'The profile photo may not be greater than 5MB.',
        ]);

        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Store new photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        if (isset($validated['bio'])) {
            $user->bio = $validated['bio'];
        }

        $user->save();

        return redirect()
            ->route('profile.show')
            ->with('success', 'Your profile has been updated successfully.');
    }

    public function settings()
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $sessionTimeout = ApplicationSetting::get('session_timeout', 120);

        return view('profile.settings', [
            'sessionTimeout' => $sessionTimeout,
        ]);
    }

    public function updateSettings(Request $request)
    {
        if (! auth()->user()->hasPermission('users')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'session_timeout' => ['required', 'integer', 'min:5', 'max:2880'], // 5 minutes to 2 days
        ], [
            'session_timeout.required' => 'Session timeout is required.',
            'session_timeout.integer' => 'Session timeout must be a number.',
            'session_timeout.min' => 'Session timeout must be at least 5 minutes.',
            'session_timeout.max' => 'Session timeout cannot exceed 2880 minutes (2 days).',
        ]);

        ApplicationSetting::set('session_timeout', $validated['session_timeout']);

        // Update Laravel session lifetime config
        config(['session.lifetime' => $validated['session_timeout']]);

        return redirect()
            ->route('profile.settings')
            ->with('success', 'Session timeout updated successfully.');
    }

    public function sessionStatus()
    {
        return response()->json([
            'active' => auth()->check(),
            'user' => auth()->user()?->only(['id', 'username', 'email']),
        ]);
    }
}
