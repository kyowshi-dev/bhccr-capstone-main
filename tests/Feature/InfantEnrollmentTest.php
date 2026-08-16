<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class InfantEnrollmentTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'immunizations',
            'description' => 'Immunizations module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authorizedUser(): User
    {
        return $this->createUserWithPermissions(['immunizations']);
    }

    private function zone(int $id): void
    {
        DB::table('zones')->insertOrIgnore(['id' => $id, 'zone_number' => 'Zone '.$id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function infantPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => now()->subDays(40)->toDateString(),
            'birth_weight' => '3.2',
            'mother_name' => 'Maria Dela Cruz',
            'guardian_name' => 'Maria Dela Cruz',
        ], $overrides);
    }

    private function motherPatient(int $zoneId = 1): Patient
    {
        $this->zone($zoneId);

        return Patient::create([
            'household_id' => Household::create(['zone_id' => $zoneId, 'family_name_head' => 'Reyes'])->id,
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'sex' => 'Female',
            'date_of_birth' => now()->subYears(28)->toDateString(),
            'civil_status' => 'Married',
            'mother_name' => 'Luz Reyes',
            'spouse_name' => 'Juan Reyes',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone '.$zoneId.' Sta. Ana',
            'is_immunization_enrolled' => false,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('immunizations.enroll-infant'), $this->infantPayload())
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload())
            ->assertForbidden();
    }

    public function test_enroll_attaches_infant_to_existing_household(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'household_id' => $household->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'guardian_name' => 'Maria Dela Cruz',
            'birth_weight' => '3.20',
            'is_immunization_enrolled' => true,
        ]);
    }

    public function test_enroll_creates_new_household_branch(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(2);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'create_household' => 1,
            'zone_id' => 2,
            'family_name_head' => 'Santos',
        ]))->assertRedirect();

        $this->assertDatabaseHas('households', ['zone_id' => 2, 'family_name_head' => 'Santos']);
        $this->assertDatabaseHas('patients', [
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'guardian_name' => 'Maria Dela Cruz',
            'is_immunization_enrolled' => true,
        ]);
    }

    public function test_enroll_infant_sets_immunization_flag(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        $response = $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
        ]))->assertRedirect();

        $patientId = Patient::where('household_id', $household->id)->first()->id;
        $this->assertSame(1, (int) DB::table('patients')->where('id', $patientId)->value('is_immunization_enrolled'));
    }

    public function test_enroll_requires_either_household_or_create_branch(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload())
            ->assertSessionHasErrors('household_id');

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_enroll_rejects_duplicate_infant_in_same_household(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload(['household_id' => $household->id]))
            ->assertRedirect();

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload(['household_id' => $household->id]))
            ->assertSessionHasErrors('duplicate');

        $this->assertSame(1, Patient::where('household_id', $household->id)->count());
    }

    public function test_household_match_returns_matching_households(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);
        Household::create(['zone_id' => 1, 'family_name_head' => 'Santos']);

        $response = $this->get(route('immunizations.household-match', ['surname' => 'Dela']))
            ->assertOk()
            ->assertJsonCount(1);

        $this->assertSame('Dela Cruz', $response->json('0.family_name_head'));
        $this->assertArrayHasKey('patients_count', $response->json('0'));
    }

    public function test_household_match_filters_by_zone(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $this->zone(2);
        Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);
        Household::create(['zone_id' => 2, 'family_name_head' => 'Dela Cruz']);

        $this->get(route('immunizations.household-match', ['surname' => 'Dela', 'zone_id' => 2]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.zone_id', 2);
    }

    public function test_household_match_requires_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->get(route('immunizations.household-match', ['surname' => 'Dela']))
            ->assertForbidden();
    }

    public function test_enroll_requires_birth_weight(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
            'birth_weight' => '',
        ]))->assertSessionHasErrors('birth_weight');

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_enroll_requires_mother_name(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
            'mother_name' => '',
        ]))->assertSessionHasErrors('mother_name');

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_enroll_persists_father_name(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
            'father_name' => 'Juan Dela Cruz',
        ]))->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'household_id' => $household->id,
            'father_name' => 'Juan Dela Cruz',
        ]);
    }

    public function test_enroll_links_mother_to_existing_patient(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);
        $mother = $this->motherPatient(1);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
            'mother_id' => $mother->id,
            'mother_name' => 'Maria Santos Reyes',
        ]))->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'household_id' => $household->id,
            'mother_id' => $mother->id,
            'mother_name' => 'Maria Santos Reyes',
        ]);
    }

    public function test_enroll_derives_mother_name_from_linked_patient(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);
        $mother = $this->motherPatient(1);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
            'mother_id' => $mother->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'household_id' => $household->id,
            'mother_id' => $mother->id,
            'mother_name' => 'Maria Santos Reyes',
        ]);
    }

    public function test_enroll_rejects_male_patient_as_mother(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        $male = Patient::create([
            'household_id' => $household->id,
            'first_name' => 'Juan',
            'last_name' => 'Reyes',
            'sex' => 'Male',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'civil_status' => 'Married',
            'mother_name' => 'Luz Reyes',
            'spouse_name' => 'Maria Reyes',
            'family_relationship' => 'Father',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => false,
        ]);

        $this->post(route('immunizations.enroll-infant'), $this->infantPayload([
            'household_id' => $household->id,
            'mother_id' => $male->id,
        ]))->assertSessionHasErrors('mother_id');

        $this->assertDatabaseMissing('patients', ['first_name' => 'Baby', 'last_name' => 'Dela Cruz']);
    }

    public function test_mother_match_returns_female_adult_patients(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->zone(1);
        $mother = $this->motherPatient(1);

        $male = Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Reyes'])->id,
            'first_name' => 'Mario',
            'last_name' => 'Reyes',
            'sex' => 'Male',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'civil_status' => 'Married',
            'mother_name' => 'Luz Reyes',
            'spouse_name' => 'Maria Reyes',
            'family_relationship' => 'Father',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => false,
        ]);

        $child = Patient::create([
            'household_id' => $male->household_id,
            'first_name' => 'Mia',
            'last_name' => 'Reyes',
            'sex' => 'Female',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria Reyes',
            'spouse_name' => '',
            'family_relationship' => 'Daughter',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => false,
        ]);

        $response = $this->get(route('immunizations.mother-match', ['query' => 'Reyes']))
            ->assertOk();

        $ids = collect($response->json())->pluck('id');

        $this->assertContains($mother->id, $ids);
        $this->assertNotContains($male->id, $ids);
        $this->assertNotContains($child->id, $ids);
    }

    public function test_mother_match_requires_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->get(route('immunizations.mother-match', ['query' => 'Reyes']))
            ->assertForbidden();
    }
}
