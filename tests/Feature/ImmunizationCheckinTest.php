<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ImmunizationCheckinTest extends TestCase
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

    private function userWithPermission(): User
    {
        return $this->createUserWithPermissions(['immunizations']);
    }

    private function infant(Carbon $dob): Patient
    {
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1', 'created_at' => now(), 'updated_at' => now()]);

        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => $dob->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria',
            'spouse_name' => '',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);
    }

    public function test_checkin_guest_is_redirected_to_login(): void
    {
        $infant = $this->infant(now()->subDays(70));

        $this->get(route('immunizations.checkin', $infant))->assertRedirect(route('login'));
    }

    public function test_checkin_requires_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));
        $infant = $this->infant(now()->subDays(70));

        $this->get(route('immunizations.checkin', $infant))->assertForbidden();
    }

    public function test_checkin_renders_schedule_fragment_for_infant(): void
    {
        $this->actingAs($this->userWithPermission());
        $infant = $this->infant(now()->subDays(70));

        $this->get(route('immunizations.checkin', $infant))
            ->assertOk()
            ->assertSee('Dela Cruz, Baby', false)
            ->assertSee('Immunization schedule', false)
            ->assertSee('Record dose', false)
            ->assertSee('Full record', false);
    }

    public function test_checkin_renders_schedule_fragment_for_adult(): void
    {
        $this->actingAs($this->userWithPermission());
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1', 'created_at' => now(), 'updated_at' => now()]);

        $adult = Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Adult',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'civil_status' => 'Married',
            'mother_name' => 'Maria',
            'spouse_name' => 'Juan',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);

        $this->get(route('immunizations.checkin', $adult))
            ->assertOk()
            ->assertSee('Dela Cruz, Adult', false)
            ->assertSee('Immunization schedule', false);
    }

    public function test_enroll_infant_page_guest_is_redirected_to_login(): void
    {
        $this->get(route('immunizations.enroll-infant.create'))->assertRedirect(route('login'));
    }

    public function test_enroll_infant_page_requires_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->get(route('immunizations.enroll-infant.create'))->assertForbidden();
    }

    public function test_enroll_infant_page_renders_form_with_zones(): void
    {
        $this->actingAs($this->userWithPermission());
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1', 'created_at' => now(), 'updated_at' => now()]);

        $this->get(route('immunizations.enroll-infant.create'))
            ->assertOk()
            ->assertViewHas('zones')
            ->assertSee('Enroll infant', false)
            ->assertSee('First name', false)
            ->assertSee('Zone 1', false);
    }
}
