<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class InputCharacterSecurityTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['patients', 'household', 'zones', 'immunizations', 'consultations', 'users'] as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'description' => $permission,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function zone(): void
    {
        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function patientPayload(array $overrides = []): array
    {
        return array_merge([
            'create_new_household' => 1,
            'new_household_zone_id' => 1,
            'new_household_family_name_head' => 'Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'birth_place' => 'Sta. Ana, Tagoloan',
            'civil_status' => 'Single (Walang Asawa)',
            'mother_name' => 'Maria Dela Cruz',
            'spouse_name' => 'Rosa Dela Cruz',
            'family_relationship' => 'Son',
            'is_philhealth_member' => 'n',
            'is_pcb_member' => 'n',
        ], $overrides);
    }

    // ============================================================
    // SCRIPT / MARKUP-LIKE INPUT REJECTION
    // ============================================================

    public function test_script_tag_rejected_in_patient_name_fields(): void
    {
        $this->actingAs($this->createUserWithPermissions(['patients']));
        $this->zone();

        $this->post(route('patients.store'), $this->patientPayload([
            'new_household_family_name_head' => '<script>alert(1)</script>',
            'first_name' => 'Juan<script>',
            'last_name' => 'Dela Cruz',
            'middle_name' => '<script>',
            'suffix' => 'Jr.<script>',
            'birth_place' => 'Sta. Ana <script>alert(1)</script>',
            'mother_name' => '<script>alert(1)</script>',
            'spouse_name' => '<script>alert(1)</script>',
        ]))->assertSessionHasErrors([
            'new_household_family_name_head',
            'first_name',
            'middle_name',
            'suffix',
            'birth_place',
            'mother_name',
            'spouse_name',
        ]);

        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('households', 0);
    }

    public function test_encoded_script_tag_rejected_in_patient_name_fields(): void
    {
        $this->actingAs($this->createUserWithPermissions(['patients']));
        $this->zone();

        $this->post(route('patients.store'), $this->patientPayload([
            'mother_name' => '&#x3C;script&#x3E;alert(1)&#x3C;/script&#x3E;',
            'spouse_name' => '＆lt;script＆gt;alert(1)＆lt;/script＆gt;',
        ]))->assertSessionHasErrors(['mother_name', 'spouse_name']);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_script_tag_rejected_in_household_store_and_update(): void
    {
        $this->actingAs($this->createUserWithPermissions(['household']));
        $this->zone();

        $this->post(route('households.store'), [
            'zone_id' => 1,
            'family_name_head' => '<script>alert(1)</script>',
        ])->assertSessionHasErrors('family_name_head');

        $this->assertDatabaseCount('households', 0);
    }

    public function test_script_tag_rejected_in_zone_number(): void
    {
        $this->actingAs($this->createUserWithPermissions(['zones']));

        $this->post(route('zones.store'), [
            'zone_number' => '<script>alert(1)</script>',
        ])->assertSessionHasErrors('zone_number');

        $this->assertDatabaseCount('zones', 0);
    }

    public function test_script_tag_rejected_in_infant_enrollment_names(): void
    {
        $this->actingAs($this->createUserWithPermissions(['immunizations']));
        $this->zone();

        $this->post(route('immunizations.enroll-infant'), [
            'create_household' => 1,
            'zone_id' => 1,
            'family_name_head' => '<script>alert(1)</script>',
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => now()->subDays(40)->toDateString(),
            'birth_weight' => '3.2',
            'mother_name' => '<script>alert(1)</script>',
            'father_name' => '<script>alert(1)</script>',
            'guardian_name' => '<script>alert(1)</script>',
        ])->assertSessionHasErrors([
            'family_name_head',
            'mother_name',
            'father_name',
            'guardian_name',
        ]);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_script_tag_rejected_in_user_names(): void
    {
        $roleId = DB::table('user_roles')->insertGetId(['role_name' => 'TestRole']);
        $this->actingAs($this->createUserWithPermissions(['users']));

        $this->post(route('users.store'), [
            'first_name' => '<script>alert(1)</script>',
            'last_name' => 'Doe',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!@#',
            'password_confirmation' => 'Password123!@#',
            'role_id' => $roleId,
        ])->assertSessionHasErrors('first_name');

        $this->assertDatabaseMissing('users', ['username' => 'testuser']);
    }

    // ============================================================
    // LEGITIMATE UNICODE / APOSTROPHE NAMES STILL ACCEPTED
    // ============================================================

    public function test_unicode_and_apostrophe_names_are_accepted(): void
    {
        $this->actingAs($this->createUserWithPermissions(['patients']));
        $this->zone();

        $this->post(route('patients.store'), $this->patientPayload([
            'first_name' => 'Peña',
            'last_name' => "O'Brien-Reyes",
            'middle_name' => 'Dela Cruz',
            'suffix' => 'Jr.',
            'mother_name' => 'María Josefa',
            'spouse_name' => 'Juan Peña Jr.',
        ]))->assertSessionHasNoErrors()
            ->assertRedirect(route('patients.index'));

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Peña',
            'last_name' => "O'brien-reyes",
            'middle_name' => 'Dela cruz',
            'suffix' => 'Jr.',
            'mother_name' => 'María Josefa',
            'spouse_name' => 'Juan Peña Jr.',
        ]);
    }

    // ============================================================
    // FREE-TEXT FIELDS: STORED RAW, RENDERED ESCAPED
    // ============================================================

    public function test_free_text_inputs_store_raw_but_render_escaped(): void
    {
        $user = $this->createUserWithPermissions(['consultations']);
        $this->actingAs($user);

        $this->zone();
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Dela Cruz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'A',
            'sex' => 'Male',
            'date_of_birth' => '2000-01-01',
            'birth_place' => 'Sta. Ana, Tagoloan',
            'blood_type' => 'O+',
            'civil_status' => 'Single (Walang Asawa)',
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
        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Nurse',
            'role' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $workerId,
            'status' => 'doctor_review',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'complaint_text' => '<script>alert(1)</script>',
            'notes' => '<img src=x onerror=alert(2)>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $show = $this->get(route('consultations.show', $consultationId));
        $show->assertOk();
        $show->assertSee('&lt;script&gt;', false);
        $show->assertDontSee('<script>alert(1)</script>', false);

        $edit = $this->get(route('consultations.edit', $consultationId));
        $edit->assertOk();
        $edit->assertSee('&lt;img src=x onerror=alert(2)&gt;', false);
        $edit->assertDontSee('<img src=x onerror=alert(2)>', false);
    }
}
