<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InfantImmunizationSeeder extends Seeder
{
    public function run(): void
    {
        $zones = DB::table('zones')->pluck('zone_number', 'id')->toArray();

        if ($zones === []) {
            $this->command?->warn('InfantImmunizationSeeder skipped: no zones seeded.');

            return;
        }

        $workers = DB::table('health_workers')
            ->whereIn('role', ['Midwife', 'Nurse', 'BHW'])
            ->get();

        if ($workers->isEmpty()) {
            $this->command?->warn('InfantImmunizationSeeder skipped: no health workers seeded.');

            return;
        }

        $sixToFourteenWeeks = [
            ['PENTA', 1], ['PENTA', 2], ['PENTA', 3],
            ['OPV', 1], ['OPV', 2], ['OPV', 3],
            ['PCV', 1], ['PCV', 2], ['PCV', 3],
            ['ROTA', 1], ['ROTA', 2],
            ['IPV', 1],
        ];

        $infants = [
            [
                'first_name' => 'Ethan', 'last_name' => 'Fernandez', 'sex' => 'Male',
                'age_days' => 6,
                'doses' => [['BCG', 1], ['HEPA_B_24H', 1]],
                'delay' => 0,
            ],
            [
                'first_name' => 'Sophia', 'last_name' => 'Lopez', 'sex' => 'Female',
                'age_days' => 56,
                'doses' => [['BCG', 1], ['HEPA_B_GT24', 1], ['PENTA', 1], ['OPV', 1], ['PCV', 1], ['ROTA', 1]],
            ],
            [
                'first_name' => 'Liam', 'last_name' => 'Martinez', 'sex' => 'Male',
                'age_days' => 84,
                'doses' => [['BCG', 1], ['HEPA_B_GT24', 1], ['PENTA', 1], ['PENTA', 2], ['OPV', 1], ['OPV', 2], ['PCV', 1], ['PCV', 2], ['ROTA', 1], ['ROTA', 2]],
            ],
            [
                'first_name' => 'Isabella', 'last_name' => 'Gonzales', 'sex' => 'Female',
                'age_days' => 112,
                'doses' => array_merge([['BCG', 1], ['HEPA_B_GT24', 1]], $sixToFourteenWeeks),
            ],
            [
                'first_name' => 'Noah', 'last_name' => 'Perez', 'sex' => 'Male',
                'age_days' => 285,
                'doses' => array_merge([['BCG', 1], ['HEPA_B_GT24', 1]], $sixToFourteenWeeks, [['MCV_AMV', 1]]),
            ],
            [
                'first_name' => 'Chloe', 'last_name' => 'Tan', 'sex' => 'Female',
                'age_days' => 400,
                'doses' => array_merge([['BCG', 1], ['HEPA_B_GT24', 1]], $sixToFourteenWeeks, [['MCV_AMV', 1], ['MCV_MMR', 1], ['HEPA_A', 1]]),
            ],
            [
                'first_name' => 'Lucas', 'last_name' => 'Uy', 'sex' => 'Male',
                'age_days' => 240,
                'doses' => [['BCG', 1], ['HEPA_B_GT24', 1], ['PENTA', 1], ['PENTA', 2], ['OPV', 1], ['OPV', 2], ['PCV', 1], ['PCV', 2], ['ROTA', 1], ['ROTA', 2]],
            ],
            [
                'first_name' => 'Mia', 'last_name' => 'Dizon', 'sex' => 'Female',
                'age_days' => 240,
                'doses' => [['BCG', 1], ['HEPA_B_GT24', 1], ['PENTA', 1], ['OPV', 1], ['PCV', 1], ['ROTA', 1]],
                'missed' => ['PENTA', 2],
            ],
        ];

        $seededFirstNames = array_column($infants, 'first_name');

        if (DB::table('patients')->whereIn('first_name', $seededFirstNames)->where('is_immunization_enrolled', true)->exists()) {
            $this->command?->warn('InfantImmunizationSeeder skipped: seeded infants already exist.');

            return;
        }

        $now = now();
        $vaccineIds = DB::table('vaccines_lookup')->pluck('id', 'vaccine_code')->toArray();
        $schedules = DB::table('vaccine_schedules')
            ->get()
            ->keyBy(fn ($schedule) => $schedule->vaccine_id.'-'.$schedule->dose_number);

        $middleNames = ['Santiago', 'Dela Cruz', 'Gutierrez', 'Marquez', 'Castillo', 'Ocampo', 'Villanueva', 'Salazar'];

        foreach ($infants as $infant) {
            $zoneId = array_rand($zones);
            $zoneNumber = $zones[$zoneId];

            $householdId = DB::table('households')->insertGetId([
                'zone_id' => $zoneId,
                'family_name_head' => $infant['last_name'],
                'contact_number' => '09'.rand(100000000, 999999999),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $dob = Carbon::today()->subDays($infant['age_days']);
            $delay = $infant['delay'] ?? rand(0, 6);

            $patientId = DB::table('patients')->insertGetId([
                'household_id' => $householdId,
                'last_name' => $infant['last_name'],
                'first_name' => $infant['first_name'],
                'middle_name' => $middleNames[array_rand($middleNames)],
                'sex' => $infant['sex'],
                'date_of_birth' => $dob->toDateString(),
                'birth_place' => 'Sta. Ana, Tagoloan',
                'blood_type' => ['A+', 'B+', 'O+', 'AB+'][rand(0, 3)],
                'civil_status' => 'Single',
                'mother_name' => 'Maria '.$infant['last_name'],
                'spouse_name' => '',
                'family_relationship' => $infant['sex'] === 'Male' ? 'Son' : 'Daughter',
                'residential_address' => $zoneNumber.' Sta. Ana, Tagoloan',
                'is_philhealth_member' => 'n',
                'is_pcb_member' => 'n',
                'has_4ps' => false,
                'has_nhts' => false,
                'is_immunization_enrolled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($infant['doses'] as [$code, $doseNumber]) {
                $vaccineId = $vaccineIds[$code] ?? null;

                if ($vaccineId === null) {
                    continue;
                }

                $schedule = $schedules->get($vaccineId.'-'.$doseNumber);

                if ($schedule === null) {
                    continue;
                }

                $dateGiven = $dob->copy()->addDays((int) $schedule->min_age_days + $delay);

                if ($dateGiven->gt(Carbon::today())) {
                    $dateGiven = Carbon::today();
                }

                DB::table('immunization_records')->insert([
                    'patient_id' => $patientId,
                    'vaccine_id' => $vaccineId,
                    'dose_number' => $doseNumber,
                    'date_given' => $dateGiven->toDateString(),
                    'temp_recorded' => rand(360, 375) / 10,
                    'administered_by' => $workers->where('role', 'Nurse')->first()?->id ?? $workers->where('role', 'Midwife')->first()?->id ?? $workers->random()->id,
                    'notes' => null,
                    'no_show' => false,
                    'no_show_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (isset($infant['missed'])) {
                [$missedCode, $missedDose] = $infant['missed'];
                $missedVaccineId = $vaccineIds[$missedCode] ?? null;
                $missedSchedule = $missedVaccineId !== null
                    ? $schedules->get($missedVaccineId.'-'.$missedDose)
                    : null;

                $userId = DB::table('users')->where('username', 'midwife')->value('id') ?? DB::table('users')->value('id');

                DB::table('immunization_status_events')->insert([
                    'patient_id' => $patientId,
                    'vaccine_id' => $missedVaccineId,
                    'dose_number' => $missedDose,
                    'event_type' => 'missed',
                    'event_date' => $dob->copy()->addDays((int) ($missedSchedule?->min_age_days ?? 70) + 3)->toDateString(),
                    'note' => 'Missed scheduled dose',
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command?->info('InfantImmunizationSeeder: seeded '.count($infants).' infants with immunization records.');
    }
}
