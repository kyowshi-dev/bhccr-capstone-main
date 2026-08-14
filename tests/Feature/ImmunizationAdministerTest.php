<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccine;
use App\Services\ChildImmunizationService;
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
            'is_immunization_enrolled' => true,
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
        ])->assertRedirect(route('immunizations.patient', $infant->id))->assertSessionHasErrors('date_given');
    }

    public function test_administer_allows_overdue_dose(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(100));

        $this->from(route('immunizations.patient', $infant->id))
            ->post(route('immunizations.administer', $infant->id), [
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

        $this->from(route('immunizations.patient', $infant->id))
            ->post(route('immunizations.administer', $infant->id), [
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

        $nextDue = app(ChildImmunizationService::class)->nextDoseDate($infant, $this->vaccine('PENTA'));

        $this->assertTrue($nextDue?->isSameDay(now()->addDays(28)));
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

    public function test_mark_done_records_overdue_dose_as_today_without_date_or_temp(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(100));
        $pentA = $this->vaccine('PENTA');

        $this->post(route('immunizations.mark-done', [$infant->id, $pentA->id]))
            ->assertRedirect(route('immunizations.index'))
            ->assertSessionHasNoErrors();

        $record = Immunization::where('patient_id', $infant->id)
            ->where('vaccine_id', $pentA->id)
            ->firstOrFail();

        $this->assertSame(1, $record->dose_number);
        $this->assertTrue($record->date_given->isSameDay(now()));
        $this->assertNull($record->temp_recorded);
        $this->assertStringContainsString('administered elsewhere', (string) $record->notes);
    }

    public function test_mark_done_still_rejects_too_early_dose(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(30));

        $this->post(route('immunizations.mark-done', [$infant->id, $this->vaccine('PENTA')->id]))
            ->assertSessionHasErrors('date_given');

        $this->assertDatabaseMissing('immunization_records', [
            'patient_id' => $infant->id,
            'vaccine_id' => $this->vaccine('PENTA')->id,
        ]);
    }

    public function test_mark_done_is_permission_gated(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));
        $infant = $this->makeInfant(now()->subDays(100));

        $this->post(route('immunizations.mark-done', [$infant->id, $this->vaccine('PENTA')->id]))
            ->assertForbidden();
    }

    public function test_mark_done_removes_no_show_placeholder_for_that_dose(): void
    {
        $this->actingAs($this->authorizedUser());
        $infant = $this->makeInfant(now()->subDays(100));
        $pentA = $this->vaccine('PENTA');

        Immunization::create([
            'patient_id' => $infant->id,
            'vaccine_id' => $pentA->id,
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'no_show' => true,
            'no_show_at' => now(),
        ]);

        $this->post(route('immunizations.mark-done', [$infant->id, $pentA->id]))
            ->assertRedirect(route('immunizations.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('immunization_records', [
            'patient_id' => $infant->id,
            'vaccine_id' => $pentA->id,
            'dose_number' => 1,
            'no_show' => 0,
        ]);
    }
}
