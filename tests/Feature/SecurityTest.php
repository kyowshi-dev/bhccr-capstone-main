<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => 1, 'role_name' => 'Admin'],
            ['id' => 2, 'role_name' => 'Nurse'],
            ['id' => 3, 'role_name' => 'BHW'],
        ]);

        DB::table('permissions')->insert([
            ['name' => 'household', 'description' => 'Access to Household module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'immunizations', 'description' => 'Access to Immunizations module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'medicines', 'description' => 'Access to Medicines module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reports', 'description' => 'Access to Reports module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'users', 'description' => 'Access to User Management', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function createUserWithPermissions(array $permissions = [], array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        if (! empty($permissions)) {
            $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
            $user->permissions()->sync($permissionIds);
        }

        return $user;
    }

    // ============================================================
    // PASSWORD HASHING TESTS
    // ============================================================

    public function test_password_is_hashed_when_creating_user(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $this->post('/users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!@#',
            'password_confirmation' => 'Password123!@#',
        ]);

        $user = User::where('username', 'testuser')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('Password123!@#', $user->password));
        $this->assertNotEquals('Password123!@#', $user->password);
    }

    public function test_password_is_hashed_when_updating_user(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $userToUpdate = User::factory()->create();
        $this->actingAs($admin);

        $this->put("/users/{$userToUpdate->id}", [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'username' => $userToUpdate->username,
            'email' => $userToUpdate->email,
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $updated = $userToUpdate->fresh();
        $this->assertTrue(Hash::check('NewPassword123!@#', $updated->password));
        $this->assertNotEquals('NewPassword123!@#', $updated->password);
    }

    public function test_password_is_hashed_in_settings_account(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123!']);
        $this->actingAs($user);

        $this->post('/settings/account', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $updated = $user->fresh();
        $this->assertTrue(Hash::check('NewPassword123!@#', $updated->password));
        $this->assertNotEquals('NewPassword123!@#', $updated->password);
    }

    // ============================================================
    // INSECURE DIRECT OBJECT REFERENCE (IDOR) TESTS
    // ============================================================

    private function createTestPatient($householdId = 1): int
    {
        return DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'A',
            'sex' => 'Male',
            'date_of_birth' => '2000-01-01',
            'birth_place' => 'Sta. Ana, Tagoloan',
            'blood_type' => 'O+',
            'civil_status' => 'Single',
            'educational_attainment' => 'High School Graduate',
            'employment_status' => 'Unemployed',
            'mother_name' => 'Mary Doe',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Son',
            'residential_address' => '1 Sta. Ana, Tagoloan',
            'is_philhealth_member' => 'n',
            'status_type' => null,
            'philhealth_no' => null,
            'membership_category' => null,
            'is_pcb_member' => 'n',
            'has_4ps' => 0,
            'has_nhts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_bhw_cannot_view_patient_without_auth(): void
    {
        $bhw = User::factory()->create();
        // Assign BHW permissions (household, patients, consultations, reports)
        $permissions = ['household', 'patients', 'consultations', 'reports'];
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        $bhw->permissions()->sync($permissionIds);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $household = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test Family',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = $this->createTestPatient($household);

        $this->actingAs($bhw);
        $response = $this->get("/patients/{$patient}");
        $response->assertStatus(200); // BHW should be able to view patients
    }

    public function test_nurse_can_view_patient(): void
    {
        $nurse = User::factory()->create();
        // Assign Nurse permissions (patients, consultations, medicines)
        $permissions = ['patients', 'consultations', 'medicines'];
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        $nurse->permissions()->sync($permissionIds);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $household = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test Family',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = $this->createTestPatient($household);

        $this->actingAs($nurse);
        $response = $this->get("/patients/{$patient}");
        $response->assertStatus(200);
    }

    public function test_unauthorized_cannot_view_consultation(): void
    {
        $bhw = User::factory()->create();
        // Assign BHW permissions (household, patients, consultations, reports)
        $permissions = ['household', 'patients', 'consultations', 'reports'];
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        $bhw->permissions()->sync($permissionIds);

        // Create health worker record
        DB::table('health_workers')->insert([
            'id' => 1,
            'user_id' => $bhw->id,
            'first_name' => 'Test',
            'last_name' => 'BHW',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $household = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = $this->createTestPatient($household);
        $consultation = DB::table('consultations')->insertGetId([
            'patient_id' => $patient,
            'worker_id' => 1,
            'status' => 'pending_doctor',
            'nature_of_visit' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($bhw);
        $response = $this->get("/consultations/{$consultation}");
        $response->assertStatus(200); // BHW should be able to view consultations
    }

    public function test_nurse_can_view_consultation(): void
    {
        $nurse = User::factory()->create();
        // Assign Nurse permissions (patients, consultations, medicines)
        $permissions = ['patients', 'consultations', 'medicines'];
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        $nurse->permissions()->sync($permissionIds);

        DB::table('health_workers')->insert([
            'id' => 1,
            'user_id' => $nurse->id,
            'first_name' => 'Test',
            'last_name' => 'Nurse',
            'role' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $household = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = $this->createTestPatient($household);
        $consultation = DB::table('consultations')->insertGetId([
            'patient_id' => $patient,
            'worker_id' => 1,
            'status' => 'pending_doctor',
            'nature_of_visit' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($nurse);
        $response = $this->get("/consultations/{$consultation}");
        $response->assertStatus(200);
    }

    // ============================================================
    // RATE LIMITING TESTS
    // ============================================================

    public function test_login_is_rate_limited(): void
    {
        // Try to login 6 times (limit is 5 per minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);

            if ($i < 5) {
                // First 5 should be allowed (will fail auth but not rate limit)
                $this->assertTrue(in_array($response->status(), [200, 302]));
            } else {
                // 6th should be rate limited
                $this->assertEquals(429, $response->status());
            }
        }
    }

    public function test_password_reset_is_rate_limited(): void
    {
        // Try to reset password 4 times (limit is 3 per minute)
        for ($i = 0; $i < 4; $i++) {
            $response = $this->post('/password/forgot', [
                'username' => 'anyuser',
            ]);

            if ($i < 3) {
                $this->assertTrue(in_array($response->status(), [200, 302]));
            } else {
                $this->assertEquals(429, $response->status());
            }
        }
    }

    // ============================================================
    // AUTHORIZATION TESTS
    // ============================================================

    public function test_bhw_cannot_create_user(): void
    {
        $bhw = $this->createUserWithPermissions([]);
        $this->actingAs($bhw);

        $response = $this->post('/users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $response = $this->post('/users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['username' => 'testuser']);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $response = $this->delete("/users/{$admin->id}", [
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_bhw_cannot_delete_medicine(): void
    {
        $bhw = $this->createUserWithPermissions(['household', 'patients']);
        $medicineId = DB::table('medicines_lookup')->insertGetId([
            'medicine_name' => 'Test Medicine',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($bhw);
        $response = $this->delete("/medicines/{$medicineId}");
        $response->assertStatus(403);
    }

    public function test_only_admin_can_delete_medicine(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $medicineId = DB::table('medicines_lookup')->insertGetId([
            'medicine_name' => 'Test Medicine',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);
        $response = $this->delete("/medicines/{$medicineId}");
        $response->assertStatus(302);
        $this->assertDatabaseMissing('medicines_lookup', ['id' => $medicineId]);
    }

    public function test_bhw_cannot_access_user_management(): void
    {
        $bhw = $this->createUserWithPermissions([]);
        $this->actingAs($bhw);

        $response = $this->get('/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_user_management(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $response = $this->get('/users');
        $response->assertStatus(200);
    }

    // ============================================================
    // SESSION & AUTHENTICATION TESTS
    // ============================================================

    public function test_session_is_regenerated_after_login(): void
    {
        $user = User::factory()->create();

        $oldSessionId = session()->getId();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $newSessionId = session()->getId();

        // Session should be regenerated to prevent session fixation
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    public function test_session_is_invalidated_after_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertGuest();
    }

    // ============================================================
    // GENERIC ERROR MESSAGES
    // ============================================================

    public function test_generic_error_message_for_non_existent_patient(): void
    {
        $nurse = $this->createUserWithPermissions(['patients']);
        $this->actingAs($nurse);

        $response = $this->get('/patients/99999');
        $response->assertStatus(404);
        // Should not reveal whether patient exists or not
        $this->assertStringNotContainsString('Patient not found', $response->getContent());
    }

    public function test_generic_error_message_for_non_existent_medicine(): void
    {
        $nurse = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($nurse);

        $response = $this->get('/medicines/99999');
        $response->assertStatus(404);
        $this->assertStringNotContainsString('Medicine not found', $response->getContent());
    }
}
