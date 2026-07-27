<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ROLES
        $roles = [
            ['id' => 1, 'role_name' => 'Admin'],    // Head Nurse/IT
            ['id' => 2, 'role_name' => 'Nurse'],   // HRH
            ['id' => 3, 'role_name' => 'Midwife'], // Midwife
            ['id' => 4, 'role_name' => 'BHW'],     // Encoder
            ['id' => 5, 'role_name' => 'BNS'],     // Nutrition Scholar
        ];
        DB::table('user_roles')->insertOrIgnore($roles);

        // 2. USERS (Create 1 Admin Account)
        // Username: admin | Password: password
        DB::table('users')->insertOrIgnore([
            'username' => 'admin',
            'password' => Hash::make('password'),
            'email' => 'admin@sta-ana.ph',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link Admin to Health Worker Profile
        DB::table('health_workers')->insertOrIgnore([
            'user_id' => 1,
            'first_name' => 'System',
            'last_name' => 'Admin',
            'role' => 'Head Nurse',
            'contact_number' => '09123456789',
        ]);

        // 3. PERMISSIONS
        $this->call(PermissionSeeder::class);
        $this->call(AssignInitialPermissionsSeeder::class);

        // 4. INITIAL USERS (Admin, BHW, Nurse, Doctor)
        $this->call(CreateInitialUsersSeeder::class);

        // 5. DIAGNOSIS (Common PH Diseases for Search Testing)
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

        // 6. MEDICINES (Basic RHU Formulary)
        $medicines = [
            ['medicine_name' => 'Paracetamol 500mg Tablet'],
            ['medicine_name' => 'Paracetamol 250mg/5mL Syrup'],
            ['medicine_name' => 'Amoxicillin 500mg Capsule'],
            ['medicine_name' => 'Amoxicillin 250mg/5mL Suspension'],
            ['medicine_name' => 'Losartan 50mg Tablet'],
            ['medicine_name' => 'Amlodipine 5mg Tablet'],
            ['medicine_name' => 'Metformin 500mg Tablet'],
            ['medicine_name' => 'ORS (Oral Rehydration Salts) Sachet'],
            ['medicine_name' => 'Multivitamins Capsule'],
            ['medicine_name' => 'Vitamin B Complex Tablet'],
        ];
        DB::table('medicines_lookup')->insertOrIgnore($medicines);

        // 7. COMPLAINTS (Chief Complaints)
        $complaints = [
            ['complaint' => 'Cough and Cold'],
            ['complaint' => 'Fever'],
            ['complaint' => 'Headache'],
            ['complaint' => 'Stomach Pain'],
            ['complaint' => 'Dizziness'],
            ['complaint' => 'High Blood Pressure'],
            ['complaint' => 'Wound/Injury'],
            ['complaint' => 'Prenatal Checkup'],
            ['complaint' => 'Immunization'],
        ];
        DB::table('complaint_lookup')->insertOrIgnore($complaints);

        // 8. ZONES (Barangay Sta. Ana Specific)
        $zones = [];
        for ($i = 1; $i <= 8; $i++) {
            $zones[] = ['zone_number' => "Zone $i"];
        }
        DB::table('zones')->insertOrIgnore($zones);

        // 9. VACCINES (EPI / Immunization lookup)
        $this->call(VaccineSeeder::class);

        // 10. ICD-10 diagnosis codes (optional: copy icd102019syst_codes.sql to storage/app/ or set BHCIS_ICD_SQL_PATH)
        $this->call(IcdDiagnosisSeeder::class);

        // 11. Sample Audit Logs
        $this->call(AuditLogSeeder::class);

        // 12. PATIENTS
        $this->call(PatientSeeder::class);

        // 13. CONSULTATIONS
        $this->call(ConsultationSeeder::class);
    }
}
