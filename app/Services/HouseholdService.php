<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class HouseholdService
{
    public static function findOrFail(int $id): object
    {
        $household = DB::table('households')->where('id', $id)->first();

        if (! $household) {
            abort(404, 'Household not found');
        }

        return $household;
    }

    public static function create(array $validated): void
    {
        DB::table('households')->insert([
            'zone_id' => $validated['zone_id'],
            'family_name_head' => trim($validated['family_name_head']),
            'contact_number' => $validated['contact_number'] !== null ? trim($validated['contact_number']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function update(int $id, array $validated): void
    {
        DB::table('households')->where('id', $id)->update([
            'zone_id' => $validated['zone_id'],
            'family_name_head' => trim($validated['family_name_head']),
            'contact_number' => $validated['contact_number'] !== null ? trim($validated['contact_number']) : null,
            'updated_at' => now(),
        ]);
    }

    public static function reassignZones(array $ids, int $newZoneId): void
    {
        DB::table('households')
            ->whereIn('id', $ids)
            ->update([
                'zone_id' => $newZoneId,
                'updated_at' => now(),
            ]);
    }
}
