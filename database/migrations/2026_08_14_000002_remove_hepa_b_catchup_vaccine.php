<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $catchupId = DB::table('vaccines_lookup')->where('vaccine_code', 'HEPA_B_CATCHUP')->value('id');
        $gt24Id = DB::table('vaccines_lookup')->where('vaccine_code', 'HEPA_B_GT24')->value('id');

        if ($catchupId !== null) {
            if ($gt24Id !== null) {
                DB::table('immunization_records')->where('vaccine_id', $catchupId)->update(['vaccine_id' => $gt24Id]);
                DB::table('immunization_status_events')->where('vaccine_id', $catchupId)->update(['vaccine_id' => $gt24Id]);
            }

            DB::table('vaccine_schedules')->where('vaccine_id', $catchupId)->delete();
            DB::table('vaccines_lookup')->where('id', $catchupId)->delete();
        }

        $sortOrders = [
            'PENTA' => 4,
            'OPV' => 5,
            'IPV' => 6,
            'PCV' => 7,
            'MCV_AMV' => 8,
            'MCV_MMR' => 9,
            'ROTA' => 10,
            'HEPA_A' => 11,
            'PNEUMONIA' => 12,
            'FLU' => 13,
            'PNEUMOCOCCAL' => 14,
        ];

        foreach ($sortOrders as $code => $sortOrder) {
            DB::table('vaccines_lookup')->where('vaccine_code', $code)->update(['sort_order' => $sortOrder]);
        }
    }

    public function down(): void
    {
        $now = now();

        $catchupId = DB::table('vaccines_lookup')->insertGetId([
            'vaccine_code' => 'HEPA_B_CATCHUP',
            'vaccine_name' => 'Hepa B (2-dose catch-up)',
            'description' => 'Catch-up series at 6 and 10 weeks',
            'category' => 'Child',
            'group_key' => 'HEPA_B',
            'start_after_days' => 42,
            'complete_before_days' => null,
            'sort_order' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('vaccine_schedules')->insert([
            ['vaccine_id' => $catchupId, 'dose_number' => 1, 'min_age_days' => 42, 'gap_days' => 28, 'requires_temp' => true, 'created_at' => $now, 'updated_at' => $now],
            ['vaccine_id' => $catchupId, 'dose_number' => 2, 'min_age_days' => 70, 'gap_days' => null, 'requires_temp' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $sortOrders = [
            'PENTA' => 5,
            'OPV' => 6,
            'IPV' => 7,
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
    }
};
