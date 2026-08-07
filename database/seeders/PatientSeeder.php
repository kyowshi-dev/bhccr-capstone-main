<?php

namespace Database\Seeders;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores',
            'Ramos', 'Aquino', 'Navarro', 'Villanueva', 'Domingo', 'Salazar', 'Dela Cruz',
            'Ocampo', 'Gutierrez', 'Marquez', 'Pascual', 'Castillo', 'Santiago', 'Romero',
            'Rivera', 'Morales', 'Jimenez', 'Buenaventura', 'Lacson', 'Manalo', 'Padilla', 'Soriano',
        ];

        $firstNamesMale = [
            'Juan', 'Jose', 'Miguel', 'Carlo', 'Angelo', 'Paolo', 'Marco', 'Ryan',
            'Bryan', 'Kenneth', 'Christian', 'Joshua', 'Daniel', 'Rafael', 'Emmanuel',
            'Victor', 'Francis', 'Jerome', 'Dennis', 'Mario', 'Ernesto', 'Ramon',
            'Eduardo', 'Ricardo', 'Roberto', 'Antonio', 'Carlos', 'Andres', 'Luis', 'Adrian',
        ];

        $firstNamesFemale = [
            'Maria', 'Ana', 'Juana', 'Rosa', 'Liza', 'Grace', 'Faith', 'Joy',
            'Sarah', 'Michelle', 'Kristine', 'Catherine', 'Beverly', 'Marites', 'Nenita',
            'Rosario', 'Teresita', 'Elena', 'Cristina', 'Andrea', 'Camille', 'Jasmine',
            'Nicole', 'Stephanie', 'Joanna', 'Rachel', 'Angelica', 'Carmela', 'Divina', 'Mildred',
        ];

        $patients = [];
        $zoneNumbers = DB::table('zones')->pluck('zone_number', 'id')->toArray();

        // Generate 20 dummy patients — one per seeded household.
        for ($i = 0; $i < 20; $i++) {
            $zoneId = rand(1, count($zoneNumbers));
            $householdId = DB::table('households')->insertGetId([
                'zone_id' => $zoneId,
                'family_name_head' => $lastNames[array_rand($lastNames)],
                'contact_number' => '09'.rand(100000000, 999999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $isMale = rand(0, 1);
            $firstName = $isMale ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
            $lastName = $lastNames[array_rand($lastNames)];

            $birthDate = Carbon::createFromDate(rand(1950, 2023), rand(1, 12), rand(1, 28));
            $civilStatus = rand(0, 1) ? 'Single' : 'Married';
            $isPhilhealthMember = rand(0, 1) === 1;
            $isPcbMember = rand(0, 1) === 1;
            $zoneNumber = $zoneNumbers[$zoneId] ?? $zoneId;

            $patients[] = [
                'household_id' => $householdId,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $lastNames[array_rand($lastNames)],
                'suffix' => $isMale && rand(0, 5) === 0 ? 'Jr.' : null,
                'sex' => $isMale ? 'Male' : 'Female',
                'date_of_birth' => $birthDate->format('Y-m-d'),
                'birth_place' => 'Sta. Ana, Tagoloan',
                'blood_type' => ['A+', 'B+', 'O+', 'AB+'][rand(0, 3)],
                'civil_status' => $civilStatus,
                'educational_attainment' => 'High School Graduate',
                'employment_status' => 'Unemployed',
                'mother_name' => $firstNamesFemale[array_rand($firstNamesFemale)].' '.$lastNames[array_rand($lastNames)],
                'spouse_name' => $civilStatus === 'Married'
                    ? ($isMale ? $firstNamesFemale[array_rand($firstNamesFemale)] : $firstNamesMale[array_rand($firstNamesMale)]).' '.$lastNames[array_rand($lastNames)]
                    : 'N/A',
                'family_relationship' => Patient::FAMILY_RELATIONSHIP_OPTIONS[array_rand(Patient::FAMILY_RELATIONSHIP_OPTIONS)],
                'residential_address' => $zoneNumber.' Sta. Ana, Tagoloan',
                'is_philhealth_member' => $isPhilhealthMember ? 'y' : 'n',
                'status_type' => $isPhilhealthMember ? Patient::PHILHEALTH_STATUS_TYPES[array_rand(Patient::PHILHEALTH_STATUS_TYPES)] : null,
                'philhealth_no' => $isPhilhealthMember ? sprintf('%02d-%09d-%d', rand(10, 99), rand(100000000, 999999999), rand(0, 9)) : null,
                'membership_category' => $isPhilhealthMember ? Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES[array_rand(Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES)] : null,
                'is_pcb_member' => $isPcbMember ? 'y' : 'n',
                'has_4ps' => rand(0, 1),
                'has_nhts' => rand(0, 1),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('patients')->insert($patients);
    }
}
