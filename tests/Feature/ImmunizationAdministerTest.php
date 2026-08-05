<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ImmunizationAdministerTest extends TestCase
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

    private function makeHousehold(int $zone = 1): Household
    {
        DB::table('zones')->insertOrIgnore(['id' => $zone, 'zone_number' => 'Zone '.$zone, 'created_at' => now(), 'updated_at' => now()]);

        return Household::create(['zone_id' => $zone, 'family_name_head' => 'Dela Cruz']);
    }

    private function makeInfant(Carbon $dob): Patient
    {
        return Patient::create([
            'household_id' => $this->makeHousehold()->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => $dob->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria',
            'spouse_name' => '',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);
    }

    private function vaccine(string $code): Vaccine
    {
        return Vaccine::where('vaccine_code', $code)->firstOrFail();
    }

    private function doseSeries(Patient $patient, Vaccine $vaccine, int $doses, Carbon $dob): void
    {
        foreach (range(1, $doses) as $dose) {
            Immunization::create([
                'patient_id' => $patient->id,
                'vaccine_id' => $vaccine->id,
                'dose_number' => $dose,
                'date_given' => $dob->copy()->addDays($vaccine->schedules->get($dose - 1)->min_age_days)->toDateString(),
            ]);
        }
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));
        $infant = $this->makeInfant(now()->subDays(70));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('PENTA')->id,
            'temp_recorded' => '36.5',
        ])->assertForbidden();
    }

    public function test_administer_rejects_too_early_dose(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(30));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('PENTA')->id,
            'temp_recorded' => '36.5',
        ])->assertSessionHasErrors('date_given');
    }

    public function test_administer_allows_overdue_dose(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(100));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('PENTA')->id,
            'temp_recorded' => '36.5',
        ])->assertRedirect(route('immunizations.patient', $infant->id))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('immunization_records', [
            'patient_id' => $infant->id,
            'vaccine_id' => $this->vaccine('PENTA')->id,
            'dose_number' => 1,
            'no_show' => 0,
        ]);
    }

    public function test_administer_rejects_out_of_window_without_override(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(300));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('ROTA')->id,
            'temp_recorded' => '36.5',
        ])->assertSessionHasErrors('override_reason');
    }

    public function test_administer_allows_out_of_window_with_override_reason(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(300));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('ROTA')->id,
            'temp_recorded' => '36.5',
            'override_reason' => 'Catch-up per physician advice',
        ])->assertRedirect(route('immunizations.patient', $infant->id))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('immunization_records', [
            'patient_id' => $infant->id,
            'vaccine_id' => $this->vaccine('ROTA')->id,
            'dose_number' => 1,
        ]);
    }

    public function test_administer_requires_temp_for_injectable_child_vaccine(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(70));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('PENTA')->id,
        ])->assertSessionHasErrors('temp_recorded');
    }

    public function test_administer_computes_next_due_date(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(70));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('PENTA')->id,
            'temp_recorded' => '36.5',
        ])->assertSessionHasNoErrors();

        $record = Immunization::where('vaccine_id', $this->vaccine('PENTA')->id)
            ->where('patient_id', $infant->id)
            ->where('dose_number', 1)
            ->firstOrFail();

        $this->assertTrue(Carbon::parse($record->next_due_date)->isSameDay(now()->addDays(28)));
    }

    public function test_administer_blocks_second_vaccine_in_same_vaccine_group(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(10));

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('HEPA_B_24H')->id,
            'temp_recorded' => '36.5',
        ])->assertSessionHasNoErrors();

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $this->vaccine('HEPA_B_GT24')->id,
            'temp_recorded' => '36.5',
        ])->assertSessionHasErrors('vaccine_id');
    }

    public function test_administer_rejects_completed_series(): void
    {
        $this->actingAs($this->authorizedUser());
        $dob = now()->subDays(200);
        $infant = $this->makeInfant($dob);
        $pentA = $this->vaccine('PENTA');
        $this->doseSeries($infant, $pentA, 3, $dob);

        $this->post(route('immunizations.administer', $infant->id), [
            'vaccine_id' => $pentA->id,
            'temp_recorded' => '36.5',
        ])->assertSessionHasErrors('dose_number');
    }
}
