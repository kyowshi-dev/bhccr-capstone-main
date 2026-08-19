<?php

namespace App\Services;

use App\Models\HealthWorker;
use App\Models\Zone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ZoneQueryService
{
    public static function paginated(int $perPage = 10): LengthAwarePaginator
    {
        return Zone::with('assignedWorker')
            ->orderBy('zone_number')
            ->paginate($perPage)
            ->withQueryString();
    }

    public static function healthWorkers(): Collection
    {
        return HealthWorker::query()
            ->orderBy('first_name')
            ->get();
    }
}
