<?php

namespace App\Services;

use App\Mail\ForgotPasswordOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

final class PasswordResetService
{
    public function issueOtp(User $user): void
    {
        $otp = (string) random_int(100000, 999999);
        $expiresInMinutes = 15;

        DB::table('password_resets')->insert([
            'user_id' => $user->id,
            'token' => Hash::make($otp),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($user->email)->send(new ForgotPasswordOtp($otp, $expiresInMinutes));

        session()->put('password_reset_username', $user->username);
    }

    public function verifyAndReset(string $username, string $otp, string $newPassword): User|false
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            return false;
        }

        $record = DB::table('password_resets')
            ->where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($otp, $record->token)) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        DB::table('password_resets')->where('user_id', $user->id)->update(['used' => true]);
        session()->forget('password_reset_username');

        return $user;
    }
}
