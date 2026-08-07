<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalPregnancyRegistrationTest extends TestCase
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

    private function authorizedUser(): User
    {
        return $this->createUserWithPermissions(['maternal']);
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'gravidity' => 2,
            'parity' => 1,
            'term' => 1,
            'preterm' => 0,
            'livebirth' => 1,
            'abortion' => 0,
            'lmp' => '2026-02-01',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('maternal.prenatal.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->get(route('maternal.prenatal.index'))->assertForbidden();
    }

    public function test_registers_pregnancy_with_computed_edc_and_aog(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $this->post(route('maternal.pregnancies.store', $patient->id), $this->validPayload())
            ->assertRedirect(route('maternal.prenatal.patient', $patient->id));

        $pregnancy = Pregnancy::where('patient_id', $patient->id)->firstOrFail();

        $this->assertSame(Pregnancy::STATUS_ACTIVE, $pregnancy->status);
        $this->assertSame(Carbon::parse('2026-02-01')->addDays(280)->toDateString(), $pregnancy->edc->toDateString());
        $this->assertSame((int) floor(Carbon::parse('2026-02-01')->diffInDays(Carbon::today()) / 7), $pregnancy->aog_weeks);
    }

    public function test_manual_edc_override_is_honored(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $this->post(route('maternal.pregnancies.store', $patient->id), $this->validPayload([
            'edc' => '2026-11-15',
        ]))->assertRedirect();

        $this->assertSame('2026-11-15', Pregnancy::where('patient_id', $patient->id)->first()->edc->toDateString());
    }

    public function test_pregnancy_requires_lmp(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $this->post(route('maternal.pregnancies.store', $patient->id), $this->validPayload([
            'lmp' => '',
        ]))->assertSessionHasErrors('lmp');

        $this->assertDatabaseCount('pregnancies', 0);
    }

    public function test_second_active_pregnancy_is_rejected(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        $this->post(route('maternal.pregnancies.store', $patient->id), $this->validPayload())->assertRedirect();
        $this->post(route('maternal.pregnancies.store', $patient->id), $this->validPayload([
            'lmp' => '2026-03-01',
        ]))->assertSessionHasErrors('lmp');

        $this->assertSame(1, Pregnancy::where('patient_id', $patient->id)->count());
    }

    public function test_prenatal_index_loads_active_pregnancies_for_authorized_user(): void
    {
        $this->actingAs($this->authorizedUser());
        $patient = $this->patient();

        Pregnancy::create($this->validPayload(['patient_id' => $patient->id]) + ['recorded_by' => null]);

        $this->get(route('maternal.prenatal.index'))
            ->assertOk()
            ->assertSee('Dela Cruz')
            ->assertViewHas('pregnancies');
    }
}
