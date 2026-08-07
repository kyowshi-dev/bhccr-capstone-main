<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalPermissionTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'maternal',
            'description' => 'Maternal care module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function patient(): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => '1990-05-10',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Juan Dela Cruz',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);
    }

    public function test_maternal_indexes_are_forbidden_without_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->get(route('maternal.prenatal.index'))->assertForbidden();
        $this->get(route('maternal.postnatal.index'))->assertForbidden();
        $this->get(route('maternal.family-planning.index'))->assertForbidden();
    }

    public function test_maternal_indexes_load_for_authorized_user(): void
    {
        $this->actingAs($this->createUserWithPermissions(['maternal']));

        $this->get(route('maternal.prenatal.index'))->assertOk();
        $this->get(route('maternal.postnatal.index'))->assertOk();
        $this->get(route('maternal.family-planning.index'))->assertOk();
    }

    public function test_patient_pages_are_forbidden_without_permission(): void
    {
        $patient = $this->patient();

        $user = $this->createUserWithPermissions(['patients']);
        $this->actingAs($user);

        $this->get(route('maternal.prenatal.patient', $patient->id))->assertForbidden();
        $this->get(route('maternal.postnatal.patient', $patient->id))->assertForbidden();
        $this->get(route('maternal.family-planning.patient', $patient->id))->assertForbidden();
    }

    public function test_prenatal_print_requires_maternal_permission(): void
    {
        $patient = $this->patient();

        $pregnancy = Pregnancy::create([
            'patient_id' => $patient->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => '2026-01-10',
            'edc' => '2026-10-17',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ]);

        $this->actingAs($this->createUserWithPermissions([]));

        $this->get(route('maternal.pregnancies.print', $pregnancy->id))->assertForbidden();
    }

    public function test_midwife_dashboard_renders_with_kpi_data(): void
    {
        $user = DB::table('user_roles')->where('role_name', 'Midwife')->value('id');
        if ($user === null) {
            $user = DB::table('user_roles')->insertGetId(['role_name' => 'Midwife']);
        }

        DB::table('permissions')->insertOrIgnore([
            'name' => 'maternal',
            'description' => 'Maternal care module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::factory()->create(['role_id' => $user]));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('prenatalRegistrants');
    }
}
