<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostnatalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $midwife = DB::table('health_workers')->where('role', 'Midwife')->first();

        if ($midwife === null) {
            $this->command?->warn('PostnatalRecordSeeder skipped: needs a Midwife health worker.');

            return;
        }

        $now = now();
        $mother = DB::table('patients')
            ->where('first_name', 'Michelle')
            ->where('last_name', 'Buenaventura')
            ->first();

        if ($mother === null) {
            $zoneId = DB::table('zones')->value('id');
            $householdId = DB::table('households')->insertGetId([
                'zone_id' => $zoneId,
                'family_name_head' => 'Buenaventura',
                'contact_number' => '09171234567',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $motherId = DB::table('patients')->insertGetId([
                'household_id' => $householdId,
                'last_name' => 'Buenaventura',
                'first_name' => 'Michelle',
                'middle_name' => 'Pascual',
                'sex' => 'Female',
                'date_of_birth' => '2004-05-03',
                'birth_place' => 'Sta. Ana, Tagoloan',
                'blood_type' => 'O+',
                'civil_status' => 'Married',
                'educational_attainment' => 'High School Graduate',
                'employment_status' => 'Unemployed',
                'mother_name' => 'Rosa Ocampo',
                'spouse_name' => 'Marco Buenaventura',
                'family_relationship' => 'Mother',
                'residential_address' => 'Sta. Ana, Tagoloan',
                'is_philhealth_member' => 'y',
                'is_pcb_member' => 'n',
                'has_4ps' => true,
                'has_nhts' => false,
                'is_immunization_enrolled' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $mother = (object) ['id' => $motherId, 'household_id' => $householdId, 'residential_address' => 'Sta. Ana, Tagoloan'];
        }

        if (! property_exists($mother, 'household_id') || $mother->household_id === null) {
            $mother->household_id = DB::table('patients')->where('id', $mother->id)->value('household_id');
        }

        if (DB::table('postnatal_records')->where('patient_id', $mother->id)->exists()) {
            $this->command?->info('PostnatalRecordSeeder skipped: a postnatal record already exists for this patient.');

            return;
        }

        $pregnancyId = DB::table('pregnancies')->insertGetId([
            'patient_id' => $mother->id,
            'status' => 'delivered',
            'gravidity' => 2,
            'parity' => 1,
            'term' => 1,
            'preterm' => 0,
            'livebirth' => 1,
            'abortion' => 0,
            'lmp' => '2025-10-06',
            'edc' => '2026-07-13',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'iron_taken' => true,
            'risk_flags' => json_encode(['hypertension']),
            'recorded_by' => $midwife->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $visits = [
            ['2026-01-20', 22.0, 148, '2026-02-20'],
            ['2026-02-20', 26.0, 142, '2026-03-20'],
            ['2026-03-20', 30.0, 140, '2026-04-20'],
            ['2026-04-20', 33.0, 144, '2026-05-20'],
            ['2026-05-20', 36.0, 146, '2026-06-15'],
        ];

        foreach ($visits as $visit) {
            DB::table('prenatal_visits')->insert([
                'pregnancy_id' => $pregnancyId,
                'visit_date' => $visit[0],
                'fundic_height_cm' => $visit[1],
                'fetal_heart_tone_bpm' => $visit[2],
                'next_visit_date' => $visit[3],
                'recorded_by' => $midwife->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $childId = DB::table('patients')->insertGetId([
            'household_id' => $mother->household_id,
            'last_name' => 'Buenaventura',
            'first_name' => 'Mara',
            'middle_name' => 'Buenaventura',
            'sex' => 'Female',
            'date_of_birth' => '2026-07-16',
            'birth_place' => 'Sta. Ana Health Center, Tagoloan',
            'blood_type' => 'O+',
            'civil_status' => 'Single',
            'mother_name' => 'Michelle Buenaventura',
            'spouse_name' => '',
            'family_relationship' => 'Daughter',
            'residential_address' => $mother->residential_address,
            'is_philhealth_member' => 'n',
            'is_pcb_member' => 'n',
            'has_4ps' => false,
            'has_nhts' => false,
            'is_immunization_enrolled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('postnatal_records')->insert([
            'patient_id' => $mother->id,
            'pregnancy_id' => $pregnancyId,
            'pregnancy_outcome' => 'live_birth',
            'prenatal_visits_completed' => 5,
            'place_delivered' => 'health_center',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => '2026-07-16',
            'delivery_time' => '09:45:00',
            'breastfeeding_date' => '2026-07-16',
            'breastfeeding_time' => '10:30:00',
            'postpartum_24h_date' => '2026-07-17',
            'postpartum_7d_date' => '2026-07-23',
            'postpartum_14d_date' => null,
            'postpartum_28d_date' => null,
            'danger_signs_mother' => null,
            'danger_signs_baby' => null,
            'vitamin_a_date' => '2026-07-23',
            'iron_date' => '2026-07-23',
            'iron_count' => 30,
            'child_last_name' => 'Buenaventura',
            'child_first_name' => 'Mara',
            'child_middle_name' => 'Buenaventura',
            'child_sex' => 'F',
            'child_birth_length_cm' => 49.5,
            'child_birth_weight_kg' => 3.20,
            'child_patient_id' => $childId,
            'recorded_by' => $midwife->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command?->info('Postnatal sample seeded: pregnancy '.$pregnancyId.' + 5 prenatal visits + child '.$childId.'.');
    }
}
