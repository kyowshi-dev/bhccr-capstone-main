<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HouseholdQueryService
{
    public static function zones(?User $user = null): Collection
    {
        $query = DB::table('zones')
            ->select('id', 'zone_number')
            ->orderBy('zone_number');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('id', $user->accessibleZoneIds());
        }

        return $query->get();
    }

    /**
     * Build the households query with search, zone and date range filters applied.
     */
    public static function filteredQuery(array $filters, ?User $user = null): Builder
    {
        $query = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->select('households.*', 'zones.zone_number');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('households.zone_id', $user->accessibleZoneIds());
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('households.family_name_head', 'like', "%{$search}%")
                    ->orWhere('households.contact_number', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['zone_id'])) {
            $query->where('households.zone_id', $filters['zone_id']);
        }

        if (! empty($filters['date_from'])) {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $filters['date_from'])->startOfDay();
            $query->where('households.created_at', '>=', $dateFrom);
        }

        if (! empty($filters['date_to'])) {
            $dateTo = Carbon::createFromFormat('Y-m-d', $filters['date_to'])->endOfDay();
            $query->where('households.created_at', '<=', $dateTo);
        }

        return $query;
    }

    public static function paginateIndex(array $filters, ?User $user = null): LengthAwarePaginator
    {
        return self::filteredQuery($filters, $user)
            ->orderBy('zones.zone_number')
            ->orderBy('households.family_name_head')
            ->paginate(500)
            ->withQueryString();
    }

    public static function allFiltered(array $filters, ?User $user = null): Collection
    {
        return self::filteredQuery($filters, $user)->get();
    }

    public static function byIds(array $ids, bool $ordered = false, ?User $user = null): Collection
    {
        $query = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->select('households.*', 'zones.zone_number')
            ->whereIn('households.id', $ids);

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('households.zone_id', $user->accessibleZoneIds());
        }

        if ($ordered) {
            $query->orderBy('zones.zone_number')
                ->orderBy('households.family_name_head');
        }

        return $query->get();
    }
}
