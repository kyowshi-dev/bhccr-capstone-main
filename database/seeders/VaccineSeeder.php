<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VaccineSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = [
            ['vaccine_code' => 'BCG', 'vaccine_name' => 'BCG', 'description' => 'At birth', 'category' => 'Child', 'sort_order' => 1],
            ['vaccine_code' => 'HepaB', 'vaccine_name' => 'Hepatitis B', 'description' => 'At birth', 'category' => 'Child', 'sort_order' => 2],
            ['vaccine_code' => 'PENTA1', 'vaccine_name' => 'Pentavalent 1', 'description' => '6 weeks', 'category' => 'Child', 'sort_order' => 3],
            ['vaccine_code' => 'PENTA2', 'vaccine_name' => 'Pentavalent 2', 'description' => '10 weeks', 'category' => 'Child', 'sort_order' => 4],
            ['vaccine_code' => 'PENTA3', 'vaccine_name' => 'Pentavalent 3', 'description' => '14 weeks', 'category' => 'Child', 'sort_order' => 5],
            ['vaccine_code' => 'OPV1', 'vaccine_name' => 'Oral Polio Vaccine 1', 'description' => '6 weeks', 'category' => 'Child', 'sort_order' => 6],
            ['vaccine_code' => 'OPV2', 'vaccine_name' => 'Oral Polio Vaccine 2', 'description' => '10 weeks', 'category' => 'Child', 'sort_order' => 7],
            ['vaccine_code' => 'OPV3', 'vaccine_name' => 'Oral Polio Vaccine 3', 'description' => '14 weeks', 'category' => 'Child', 'sort_order' => 8],
            ['vaccine_code' => 'MCV1', 'vaccine_name' => 'Measles Containing Vaccine 1 (AMV)', 'description' => '9 months', 'category' => 'Child', 'sort_order' => 9],
            ['vaccine_code' => 'MCV2', 'vaccine_name' => 'Measles Containing Vaccine 2 (MMR)', 'description' => '12-23 months', 'category' => 'Child', 'sort_order' => 10],
            ['vaccine_code' => 'ROTA1', 'vaccine_name' => 'Rotavirus 1', 'description' => '6 weeks', 'category' => 'Child', 'sort_order' => 11],
            ['vaccine_code' => 'ROTA2', 'vaccine_name' => 'Rotavirus 2', 'description' => '10 weeks', 'category' => 'Child', 'sort_order' => 12],
            ['vaccine_code' => 'PCV1', 'vaccine_name' => 'Pneumococcal 1', 'description' => '6 weeks', 'category' => 'Child', 'sort_order' => 13],
            ['vaccine_code' => 'PCV2', 'vaccine_name' => 'Pneumococcal 2', 'description' => '10 weeks', 'category' => 'Child', 'sort_order' => 14],
            ['vaccine_code' => 'PCV3', 'vaccine_name' => 'Pneumococcal 3', 'description' => '14 weeks', 'category' => 'Child', 'sort_order' => 15],
            ['vaccine_code' => 'Hepa B2', 'vaccine_name' => 'Hepatitis B 2', 'description' => '6 weeks', 'category' => 'Child', 'sort_order' => 16],
            ['vaccine_code' => 'Hepa B3', 'vaccine_name' => 'Hepatitis B 3', 'description' => '10 weeks', 'category' => 'Child', 'sort_order' => 17],
            ['vaccine_code' => 'Hepa A', 'vaccine_name' => 'Hepatitis A', 'description' => '12 months', 'category' => 'Child', 'sort_order' => 18],
            ['vaccine_code' => 'Pneumonia', 'vaccine_name' => 'Pneumonia', 'description' => 'As needed', 'category' => 'Child', 'sort_order' => 19],
            ['vaccine_code' => 'Pneumococcal', 'vaccine_name' => 'Pneumococcal', 'description' => 'As per schedule', 'category' => 'Adult', 'sort_order' => 20],
        ];

        $now = now();
        foreach ($vaccines as $i => $v) {
            $vaccines[$i]['created_at'] = $now;
            $vaccines[$i]['updated_at'] = $now;
        }
        foreach ($vaccines as $v) {
            DB::table('vaccines_lookup')->insertOrIgnore($v);
        }
    }
}
