<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class MedicineService
{
    public static function findOrFail(int $id): object
    {
        $medicine = DB::table('medicines_lookup')->where('id', $id)->first();

        if (! $medicine) {
            abort(404, 'Resource not found');
        }

        return $medicine;
    }

    public static function create(array $validated, bool $isActive): void
    {
        DB::table('medicines_lookup')->insert([
            'name' => $validated['name'],
            'generic_name' => $validated['generic_name'] ?? null,
            'strength' => $validated['strength'] ?? null,
            'form' => $validated['form'] ?? null,
            'manufacturer' => $validated['manufacturer'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function update(int $id, array $validated, bool $isActive): void
    {
        DB::table('medicines_lookup')->where('id', $id)->update([
            'name' => $validated['name'],
            'generic_name' => $validated['generic_name'] ?? null,
            'strength' => $validated['strength'] ?? null,
            'form' => $validated['form'] ?? null,
            'manufacturer' => $validated['manufacturer'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'is_active' => $isActive,
            'updated_at' => now(),
        ]);
    }

    public static function isUsedInPrescriptions(int $id): bool
    {
        return DB::table('prescriptions')->where('medicine_id', $id)->exists();
    }

    public static function usage(int $id): array
    {
        return [
            'prescription_count' => DB::table('prescriptions')->where('medicine_id', $id)->count(),
            'last_prescribed' => DB::table('prescriptions')
                ->where('medicine_id', $id)
                ->orderByDesc('created_at')
                ->value('created_at'),
        ];
    }

    public static function destroy(int $id): void
    {
        DB::table('medicines_lookup')->where('id', $id)->delete();
    }
}
