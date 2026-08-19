<?php

namespace App\Services;

use App\Models\Medicine;
use Illuminate\Support\Facades\DB;

final class MedicineService
{
    public static function findOrFail(int $id): Medicine
    {
        return Medicine::query()->findOrFail($id);
    }

    public static function create(array $validated): void
    {
        Medicine::query()->create([
            'name' => $validated['name'],
            'form' => $validated['form'] ?? null,
        ]);
    }

    public static function update(int $id, array $validated): void
    {
        self::findOrFail($id)->update([
            'name' => $validated['name'],
            'form' => $validated['form'] ?? null,
        ]);
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
        self::findOrFail($id)->delete();
    }

    public static function restore(int $id): void
    {
        Medicine::onlyTrashed()->findOrFail($id)->restore();
    }
}
