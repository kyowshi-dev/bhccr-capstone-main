<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class ReferralService
{
    public static function updateStatus(int $id, string $status): bool
    {
        return (bool) DB::table('outward_referrals')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
}
