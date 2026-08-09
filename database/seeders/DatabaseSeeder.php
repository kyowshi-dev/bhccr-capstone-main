<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ROLES
        $roles = [
            ['id' => 1, 'role_name' => 'Admin'],
            ['id' => 2, 'role_name' => 'Nurse'],
            ['id' => 3, 'role_name' => 'Midwife'],
            ['id' => 4, 'role_name' => 'BHW'],
            ['id' => 5, 'role_name' => 'Doctor'],
        ];
        DB::table('user_roles')->insertOrIgnore($roles);

        // 2. PERMISSIONS + role assignment
        $this->call(PermissionSeeder::class);
        $this->call(AssignInitialRolesSeeder::class);

        // 3. INITIAL USERS (Admin, BHW, Nurse, Doctor)
        // All passwords: password123
        $this->call(CreateInitialUsersSeeder::class);

        // 4. DIAGNOSIS — common PH diseases for local search testing
        $diagnoses = [
            ['diagnosis_code' => 'J00', 'diagnosis_name' => 'Acute Nasopharyngitis (Common Cold)', 'category' => 'Respiratory'],
            ['diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute Upper Respiratory Infection (URTI)', 'category' => 'Respiratory'],
            ['diagnosis_code' => 'I10', 'diagnosis_name' => 'Essential (Primary) Hypertension', 'category' => 'Circulatory'],
            ['diagnosis_code' => 'E11', 'diagnosis_name' => 'Type 2 Diabetes Mellitus', 'category' => 'Endocrine'],
            ['diagnosis_code' => 'A90', 'diagnosis_name' => 'Dengue Fever', 'category' => 'Infectious'],
            ['diagnosis_code' => 'A09', 'diagnosis_name' => 'Infectious Gastroenteritis (Diarrhea)', 'category' => 'Infectious'],
            ['diagnosis_code' => 'T14.1', 'diagnosis_name' => 'Open Wound', 'category' => 'Injury'],
        ];
        DB::table('diagnosis_lookup')->insertOrIgnore($diagnoses);

        // 5. MEDICINES — basic RHU formulary
        $medicines = [
            ['name' => 'Paracetamol'],
            ['name' => 'Amoxicillin'],
            ['name' => 'Metformin'],
            ['name' => 'Biogesic'],
            ['name' => 'Losartan'],
            ['name' => 'Amlodipine'],
            ['name' => 'Bioflu'],
            ['name' => 'ORS (Oral Rehydration Salts) Sachet'],
            ['name' => 'Cetirizine'],
            ['name' => 'Salbutamol Inhaler'],
            ['name' => 'Ibuprofen'],
            ['name' => 'Azithromycin'],
            ['name' => 'Vitamin C'],
            ['name' => 'Erethromycin'],
            ['name' => 'Diatabs'],
        ];
        $now = now();
        foreach ($medicines as $i => $m) {
            $medicines[$i]['created_at'] = $now;
            $medicines[$i]['updated_at'] = $now;
        }
        DB::table('medicines_lookup')->insertOrIgnore($medicines);

        // 6. ZONES
        $zoneIds = [];
        for ($i = 1; $i <= 7; $i++) {
            $zoneIds[] = DB::table('zones')->insertGetId([
                'zone_number' => "$i",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 6a. Assign each zone to a BHW
        $bhwIds = DB::table('health_workers')
            ->where('role', 'BHW')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        foreach ($zoneIds as $i => $zoneId) {
            if (isset($bhwIds[$i])) {
                DB::table('zones')->where('id', $zoneId)->update(['assigned_worker_id' => $bhwIds[$i]]);
            }
        }

        // 7. VACCINES — EPI / immunization lookup
        $this->call(VaccineSeeder::class);

        // 8. ICD-10 diagnosis codes (requires icd102019syst_codes.sql in storage/app/ or BHCIS_ICD_SQL_PATH)
        $this->call(IcdDiagnosisSeeder::class);

        // 9. PATIENTS + households
        $this->call(PatientSeeder::class);

        // 10. CONSULTATIONS + outward referrals
        $this->call(ConsultationSeeder::class);

        // 11. Sample postnatal record (with prior prenatal record)
        $this->call(PostnatalRecordSeeder::class);

        // 12. Sample audit logs
        $this->call(AuditLogSeeder::class);
    }
}
