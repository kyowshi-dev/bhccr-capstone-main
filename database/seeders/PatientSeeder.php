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
        // Common Filipino Names
        $lastNames = ["Smith","Johnson","Williams","Brown","Jones","Miller","Davis","Garcia","Rodriguez","Wilson","Martinez","Anderson","Taylor","Thomas","Hernandez","Moore","Martin","Jackson","Thompson","White","Lopez","Lee","Gonzalez","Harris","Clark","Lewis","Robinson","Walker","Perez","Hall","Young","Allen","Sanchez","Wright","King","Scott","Green","Baker","Adams","Nelson","Hill","Ramirez","Campbell","Mitchell","Roberts","Carter","Phillips","Evans","Turner","Torres","Parker","Collins","Edwards","Stewart","Flores","Morris","Nguyen","Murphy","Rivera","Cook","Rogers","Morgan","Peterson","Cooper","Reed","Bailey","Bell","Gomez","Kelly","Howard","Ward","Cox","Diaz","Richardson","Wood","Watson","Brooks","Bennett","Gray","James","Reyes","Cruz","Hughes","Price","Myers","Long","Foster","Sanders","Ross","Morales","Powell","Sullivan","Russell","Ortiz","Jenkins","Gutierrez","Perry","Butler","Barnes","Fisher","Henderson","Coleman","Simmons","Patterson","Jordan","Reynolds","Hamilton","Graham","Kim","Gonzales","Alexander","Ramos","Wallace","Griffin","West","Cole","Hayes","Chavez","Gibson","Bryant","Ellis","Stevens","Murray","Ford","Marshall","Owens","McDonald","Harrison","Ruiz","Kennedy","Wells","Alvarez","Woods","Mendoza","Castillo","Olson","Webb","Washington","Tucker","Freeman","Burns","Henry","Vasquez","Snyder","Simpson","Crawford","Jimenez","Porter","Mason","Shaw","Gordon","Wagner","Hunter","Romero","Hicks","Dixon","Hunt","Palmer","Robertson","Black","Holmes","Stone","Meyer","Boyd","Mills","Warren","Fox","Rose","Rice","Moreno","Schmidt","Patel","Ferguson","Nichols","Herrera"];

        $firstNamesMale = ["Jacob","Michael","Matthew","Joshua","Christopher","Nicholas","Andrew","Joseph","Daniel","Tyler","William","Brandon","Ryan","John","Zachary","David","Anthony","James","Justin","Alexander","Jonathan","Christian","Austin","Dylan","Ethan","Benjamin","Noah","Samuel","Robert","Nathan","Cameron","Kevin","Thomas","Jose","Hunter","Jordan","Kyle","Caleb","Jason","Logan","Aaron","Eric","Brian","Gabriel","Adam","Jack","Isaiah","Juan","Luis","Connor","Charles","Elijah","Isaac","Steven","Evan","Jared","Sean","Timothy","Luke","Cody","Nathaniel","Alex","Seth","Mason","Richard","Carlos","Angel","Patrick","Devin","Bryan","Cole","Jackson","Ian","Garrett","Trevor","Jesus","Chase","Adrian","Mark","Blake","Sebastian","Antonio","Lucas","Jeremy","Gavin","Miguel","Julian","Dakota","Alejandro","Jesse","Dalton","Bryce","Tanner","Kenneth","Stephen","Jake","Victor","Spencer","Marcus","Paul","Brendan","Jeremiah","Xavier","Jeffrey","Tristan","Jalen","Jorge","Edward","Riley","Wyatt","Colton","Joel","Maxwell","Aidan","Travis","Shane","Colin","Dominic","Carson","Vincent","Derek","Oscar","Grant","Eduardo","Peter","Henry","Parker","Hayden","Collin","George","Bradley","Mitchell","Devon","Ricardo","Shawn"];

        $firstNamesFemale = ["Emily","Hannah","Madison","Ashley","Sarah","Alexis","Samantha","Jessica","Elizabeth","Taylor","Lauren","Alyssa","Kayla","Abigail","Brianna","Olivia","Emma","Megan","Grace","Victoria","Rachel","Anna","Sydney","Destiny","Morgan","Jennifer","Jasmine","Haley","Julia","Kaitlyn","Nicole","Amanda","Katherine","Natalie","Hailey","Alexandra","Savannah","Chloe","Rebecca","Stephanie","Maria","Sophia","Mackenzie","Allison","Isabella","Amber","Mary","Danielle","Gabrielle","Jordan","Brooke","Michelle","Sierra","Katelyn","Andrea","Madeline","Sara","Kimberly","Courtney","Erin","Brittany","Vanessa","Jenna","Jacqueline","Caroline","Faith","Makayla","Bailey","Paige","Shelby","Melissa","Kaylee","Christina","Trinity","Mariah","Caitlin","Autumn","Marissa","Breanna","Angela","Catherine","Zoe","Briana","Jada","Laura","Claire","Alexa","Kelsey","Kathryn","Leslie","Alexandria","Sabrina","Mia","Isabel","Molly","Leah","Katie","Gabriella","Cheyenne","Cassandra","Tiffany","Erica","Lindsey","Kylie","Amy","Diana","Cassidy","Mikayla","Ariana","Margaret","Kelly","Miranda","Maya","Melanie","Audrey","Jade","Gabriela","Caitlyn","Angel","Jillian","Alicia","Jocelyn","Erika","Lily","Heather","Madelyn","Adriana","Arianna","Lillian","Kiara","Riley","Crystal","Mckenzie","Meghan","Skylar","Ana","Britney","Angelica"];

        $patients = [];
        $households = [];
        $zoneNumbers = DB::table('zones')->pluck('zone_number', 'id')->toArray();

        // Generate Dummy Patients
        for ($i = 0; $i < 693; $i++) {

            // 1. Create a Household First (Required by database schema)
            $zoneId = rand(1, 8);
            $householdId = DB::table('households')->insertGetId([
                'zone_id' => $zoneId,
                'family_name_head' => $lastNames[array_rand($lastNames)],
                'contact_number' => '09'.rand(100000000, 999999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Create the Patient
            $isMale = rand(0, 1);
            $firstName = $isMale ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
            $lastName = $lastNames[array_rand($lastNames)];

            // Random birth date between 1950 and 2023
            $birthDate = Carbon::createFromDate(rand(1950, 2023), rand(1, 12), rand(1, 28));
            $civilStatus = rand(0, 1) ? 'Single' : 'Married';
            $isPhilhealthMember = rand(0, 1) === 1;
            $isPcbMember = rand(0, 1) === 1;
            $zoneNumber = $zoneNumbers[$zoneId] ?? $zoneId;
            $relationshipOptions = Patient::FAMILY_RELATIONSHIP_OPTIONS;

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
                'mother_name' => $firstNamesFemale[array_rand($firstNamesFemale)] . ' ' . $lastNames[array_rand($lastNames)],
                'spouse_name' => $civilStatus === 'Married'
                    ? ($isMale ? $firstNamesFemale[array_rand($firstNamesFemale)] : $firstNamesMale[array_rand($firstNamesMale)]) . ' ' . $lastNames[array_rand($lastNames)]
                    : 'N/A',
                'family_relationship' => $relationshipOptions[array_rand($relationshipOptions)],
                'residential_address' => $zoneNumber . ' Sta. Ana, Tagoloan',
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
