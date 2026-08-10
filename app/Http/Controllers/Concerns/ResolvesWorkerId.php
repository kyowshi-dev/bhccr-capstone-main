<?php

namespace App\Http\Controllers\Concerns;

use App\Models\HealthWorker;
use Illuminate\Support\Facades\DB;

trait ResolvesWorkerId
{
    protected function currentWorkerId(): ?int
    {
        $workerId = DB::table('health_workers')->where('user_id', auth()->id())->value('id');

        return $workerId !== null ? (int) $workerId : null;
    }

    protected function resolveWorker(): HealthWorker
    {
        $workerId = $this->currentWorkerId();

        if ($workerId === null) {
            abort(403, 'No health worker profile is linked to this user.');
        }

        $worker = HealthWorker::find($workerId);

        if ($worker === null) {
            abort(403, 'No health worker profile is linked to this user.');
        }

        return $worker;
    }
}
