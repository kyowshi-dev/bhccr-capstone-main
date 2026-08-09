<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VaccineSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = [
            [
                'vaccine_code' => 'BCG',
                'vaccine_name' => 'BCG',
                'description' => 'At birth (within 24 hours)',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 1,
                'start_after_days' => 0,
                'complete_before_days' => 365,
            ],
            [
                'vaccine_code' => 'HEPA_B_24H',
                'vaccine_name' => 'Hepa B (w/in 24 hrs)',
                'description' => 'Birth dose within 24 hours',
                'category' => 'Child',
                'group_key' => 'HEPA_B',
                'sort_order' => 2,
                'start_after_days' => 0,
                'complete_before_days' => 1,
            ],
            [
                'vaccine_code' => 'HEPA_B_GT24',
                'vaccine_name' => 'Hepa B (≥ 24 hrs)',
                'description' => 'Late birth dose — give as soon as possible',
                'category' => 'Child',
                'group_key' => 'HEPA_B',
                'sort_order' => 3,
                'start_after_days' => 2,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'HEPA_B_CATCHUP',
                'vaccine_name' => 'Hepa B (2-dose catch-up)',
                'description' => 'Catch-up series at 6 and 10 weeks',
                'category' => 'Child',
                'group_key' => 'HEPA_B',
                'sort_order' => 4,
                'start_after_days' => 42,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'PENTA',
                'vaccine_name' => 'PENTA',
                'description' => '6, 10 and 14 weeks (DTwP-HepB-Hib)',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 5,
                'start_after_days' => 42,
                'complete_before_days' => 180,
            ],
            [
                'vaccine_code' => 'OPV',
                'vaccine_name' => 'OPV',
                'description' => '6, 10 and 14 weeks (bivalent oral polio)',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 6,
                'start_after_days' => 42,
                'complete_before_days' => 180,
            ],
            [
                'vaccine_code' => 'MCV_AMV',
                'vaccine_name' => 'MCV (AMV)',
                'description' => '9 months — measles containing vaccine',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 7,
                'start_after_days' => 270,
                'complete_before_days' => 365,
            ],
            [
                'vaccine_code' => 'MCV_MMR',
                'vaccine_name' => 'MCV (MMR)',
                'description' => '12 months — measles, mumps, rubella',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 8,
                'start_after_days' => 365,
                'complete_before_days' => 730,
            ],
            [
                'vaccine_code' => 'ROTA',
                'vaccine_name' => 'ROTA',
                'description' => '6 and 10 weeks — complete by 8 months',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 9,
                'start_after_days' => 42,
                'complete_before_days' => 240,
            ],
            [
                'vaccine_code' => 'PCV',
                'vaccine_name' => 'PCV',
                'description' => '6, 10 and 14 weeks (pneumococcal conjugate)',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 10,
                'start_after_days' => 42,
                'complete_before_days' => 180,
            ],
            [
                'vaccine_code' => 'HEPA_A',
                'vaccine_name' => 'Hepa A',
                'description' => '12 months, second dose 6 months later',
                'category' => 'Child',
                'group_key' => null,
                'sort_order' => 11,
                'start_after_days' => 365,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'PNEUMONIA',
                'vaccine_name' => 'Pneumonia',
                'description' => 'As needed — single dose',
                'category' => 'Adult',
                'group_key' => null,
                'sort_order' => 12,
                'start_after_days' => 0,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'FLU',
                'vaccine_name' => 'Influenza',
                'description' => 'Annual — adults',
                'category' => 'Adult',
                'group_key' => null,
                'sort_order' => 13,
                'start_after_days' => 0,
                'complete_before_days' => null,
            ],
            [
                'vaccine_code' => 'PNEUMOCOCCAL',
                'vaccine_name' => 'Pneumococcal',
                'description' => 'Adult schedule — as needed',
                'category' => 'Adult',
                'group_key' => null,
                'sort_order' => 14,
                'start_after_days' => 0,
                'complete_before_days' => null,
            ],
        ];

        foreach ($vaccines as $vaccine) {
            DB::table('vaccines_lookup')->updateOrInsert(
                ['vaccine_code' => $vaccine['vaccine_code']],
                array_merge($vaccine, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
