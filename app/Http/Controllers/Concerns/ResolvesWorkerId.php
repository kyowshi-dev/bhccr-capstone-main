<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;

trait ResolvesWorkerId
{
    protected function currentWorkerId(): ?int
    {
        $workerId = DB::table('health_workers')->where('user_id', auth()->id())->value('id');

        return $workerId !== null ? (int) $workerId : null;
    }
}
