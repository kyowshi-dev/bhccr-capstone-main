<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $sortOrders = [
            'PCV' => 8,
            'MCV_AMV' => 9,
            'MCV_MMR' => 10,
            'ROTA' => 11,
            'HEPA_A' => 12,
            'PNEUMONIA' => 13,
            'FLU' => 14,
            'PNEUMOCOCCAL' => 15,
        ];

        foreach ($sortOrders as $code => $sortOrder) {
            DB::table('vaccines_lookup')->where('vaccine_code', $code)->update(['sort_order' => $sortOrder]);
        }

        DB::table('vaccines_lookup')->updateOrInsert(
            ['vaccine_code' => 'IPV'],
            [
                'vaccine_name' => 'IPV',
                'description' => '14 weeks - inactivated polio',
                'category' => 'Child',
                'group_key' => null,
                'start_after_days' => 98,
                'complete_before_days' => 180,
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $ipvId = DB::table('vaccines_lookup')->where('vaccine_code', 'IPV')->value('id');

        DB::table('vaccine_schedules')->where('vaccine_id', $ipvId)->delete();

        DB::table('vaccine_schedules')->insert([
            'vaccine_id' => $ipvId,
            'dose_number' => 1,
            'min_age_days' => 98,
            'gap_days' => null,
            'requires_temp' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $ipvId = DB::table('vaccines_lookup')->where('vaccine_code', 'IPV')->value('id');

        if ($ipvId !== null) {
            DB::table('vaccine_schedules')->where('vaccine_id', $ipvId)->delete();
            DB::table('vaccines_lookup')->where('id', $ipvId)->delete();
        }

        $sortOrders = [
            'PCV' => 10,
            'MCV_AMV' => 7,
            'MCV_MMR' => 8,
            'ROTA' => 9,
            'HEPA_A' => 11,
            'PNEUMONIA' => 12,
            'FLU' => 13,
            'PNEUMOCOCCAL' => 14,
        ];

        foreach ($sortOrders as $code => $sortOrder) {
            DB::table('vaccines_lookup')->where('vaccine_code', $code)->update(['sort_order' => $sortOrder]);
        }
    }
};
