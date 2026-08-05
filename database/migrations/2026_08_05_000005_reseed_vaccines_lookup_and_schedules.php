<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $vaccines = [
            [
                'vaccine_code' => 'BCG',
                'vaccine_name' => 'BCG',
                'description' => 'At birth (within 24 hours)',
                'category' => 'Child',
                'sort_order' => 1,
                'group_key' => null,
                'start_after_days' => 0,
                'complete_before_days' => 365,
            ],
            [
                'vaccine_code' => 'HEPA_B_24H',
                'vaccine_name' => 'Hepa B (w/in 24 hrs)',
                'description' => 'Birth dose within 24 hours',
                'category' => 'Child',
                'sort_order' => 2,
                'group_key' => 'HEPA_B',
                'start_after_days' => 0,
                'complete_before_days' => 1,
            ],
            [
                'vaccine_code' => 'HEPA_B_GT24',
                'vaccine_name' => 'Hepa B (≥ 24 hrs)',
                'description' => 'Late birth dose — give as soon as possible',
                'category' => 'Child',
                'sort_order' => 3,
                'group_key' => 'HEPA_B',
                'start_after_days' => 2,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'HEPA_B_CATCHUP',
                'vaccine_name' => 'Hepa B (2-dose catch-up)',
                'description' => 'Catch-up series at 6 and 10 weeks',
                'category' => 'Child',
                'sort_order' => 4,
                'group_key' => 'HEPA_B',
                'start_after_days' => 42,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'PENTA',
                'vaccine_name' => 'PENTA',
                'description' => '6, 10 and 14 weeks (DTwP-HepB-Hib)',
                'category' => 'Child',
                'sort_order' => 5,
                'group_key' => null,
                'start_after_days' => 42,
                'complete_before_days' => 180,
            ],
            [
                'vaccine_code' => 'OPV',
                'vaccine_name' => 'OPV',
                'description' => '6, 10 and 14 weeks (bivalent oral polio)',
                'category' => 'Child',
                'sort_order' => 6,
                'group_key' => null,
                'start_after_days' => 42,
                'complete_before_days' => 180,
            ],
            [
                'vaccine_code' => 'MCV_AMV',
                'vaccine_name' => 'MCV (AMV)',
                'description' => '9 months — measles containing vaccine',
                'category' => 'Child',
                'sort_order' => 7,
                'group_key' => null,
                'start_after_days' => 270,
                'complete_before_days' => 365,
            ],
            [
                'vaccine_code' => 'MCV_MMR',
                'vaccine_name' => 'MCV (MMR)',
                'description' => '12 months — measles, mumps, rubella',
                'category' => 'Child',
                'sort_order' => 8,
                'group_key' => null,
                'start_after_days' => 365,
                'complete_before_days' => 730,
            ],
            [
                'vaccine_code' => 'ROTA',
                'vaccine_name' => 'ROTA',
                'description' => '6 and 10 weeks — complete by 8 months',
                'category' => 'Child',
                'sort_order' => 9,
                'group_key' => null,
                'start_after_days' => 42,
                'complete_before_days' => 240,
            ],
            [
                'vaccine_code' => 'PCV',
                'vaccine_name' => 'PCV',
                'description' => '6, 10 and 14 weeks (pneumococcal conjugate)',
                'category' => 'Child',
                'sort_order' => 10,
                'group_key' => null,
                'start_after_days' => 42,
                'complete_before_days' => 180,
            ],
            [
                'vaccine_code' => 'HEPA_A',
                'vaccine_name' => 'Hepa A',
                'description' => '12 months, second dose 6 months later',
                'category' => 'Child',
                'sort_order' => 11,
                'group_key' => null,
                'start_after_days' => 365,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'PNEUMONIA',
                'vaccine_name' => 'Pneumonia',
                'description' => 'As needed — single dose',
                'category' => 'Child',
                'sort_order' => 12,
                'group_key' => null,
                'start_after_days' => 0,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'FLU',
                'vaccine_name' => 'Influenza',
                'description' => 'Annual — children and adults',
                'category' => 'Both',
                'sort_order' => 13,
                'group_key' => null,
                'start_after_days' => 0,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'PNEUMOCOCCAL',
                'vaccine_name' => 'Pneumococcal',
                'description' => 'Adult schedule — as needed',
                'category' => 'Adult',
                'sort_order' => 14,
                'group_key' => null,
                'start_after_days' => 0,
                'complete_before_days' => null,
            ],
        ];

        foreach ($vaccines as $vaccine) {
            DB::table('vaccines_lookup')->updateOrInsert(
                ['vaccine_code' => $vaccine['vaccine_code']],
                array_merge($vaccine, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $remap = [
            'HepaB' => 'HEPA_B_24H',
            'Hepa B2' => 'HEPA_B_CATCHUP',
            'Hepa B3' => 'HEPA_B_CATCHUP',
            'Hepa A' => 'HEPA_A',
            'MCV1' => 'MCV_AMV',
            'MCV2' => 'MCV_MMR',
            'ROTA1' => 'ROTA',
            'ROTA2' => 'ROTA',
            'Pneumonia' => 'PNEUMONIA',
            'Pneumococcal' => 'PNEUMOCOCCAL',
        ];

        foreach ($remap as $oldCode => $newCode) {
            $newId = DB::table('vaccines_lookup')->where('vaccine_code', $newCode)->value('id');
            $oldId = DB::table('vaccines_lookup')->where('vaccine_code', $oldCode)->value('id');

            if ($newId === null || $oldId === null || $newId === $oldId) {
                continue;
            }

            DB::table('immunization_records')->where('vaccine_id', $oldId)->update(['vaccine_id' => $newId]);
            DB::table('vaccines_lookup')->where('id', $oldId)->delete();
        }

        $schedules = [
            ['BCG', [[1, 0, null]]],
            ['HEPA_B_24H', [[1, 0, null]]],
            ['HEPA_B_GT24', [[1, 2, null]]],
            ['HEPA_B_CATCHUP', [[1, 42, 28], [2, 70, null]]],
            ['PENTA', [[1, 42, 28], [2, 70, 28], [3, 98, null]]],
            ['OPV', [[1, 42, 28], [2, 70, 28], [3, 98, null]]],
            ['MCV_AMV', [[1, 270, null]]],
            ['MCV_MMR', [[1, 365, null]]],
            ['ROTA', [[1, 42, 28], [2, 70, null]]],
            ['PCV', [[1, 42, 28], [2, 70, 28], [3, 98, null]]],
            ['HEPA_A', [[1, 365, 182], [2, 547, null]]],
            ['PNEUMONIA', [[1, 0, null]]],
            ['FLU', [[1, 0, null]]],
            ['PNEUMOCOCCAL', [[1, 0, null]]],
        ];

        foreach ($schedules as [$code, $doses]) {
            $vaccineId = DB::table('vaccines_lookup')->where('vaccine_code', $code)->value('id');

            $category = DB::table('vaccines_lookup')->where('id', $vaccineId)->value('category');

            foreach ($doses as [$doseNumber, $minAgeDays, $gapDays]) {
                DB::table('vaccine_schedules')->insert([
                    'vaccine_id' => $vaccineId,
                    'dose_number' => $doseNumber,
                    'min_age_days' => $minAgeDays,
                    'gap_days' => $gapDays,
                    'requires_temp' => $category === 'Child',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('vaccine_schedules')->delete();
    }
};
