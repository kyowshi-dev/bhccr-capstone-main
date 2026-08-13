<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\DashboardQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class DashboardImmunizationKpiTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function zone(int $id): void
    {
        DB::table('zones')->insertOrIgnore(['id' => $id, 'zone_number' => 'Zone '.$id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function enrolledPatient(Carbon $dob): Patient
    {
        $this->zone(1);

        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'sex' => 'Female',
            'date_of_birth' => $dob->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria',
            'spouse_name' => '',
            'family_relationship' => 'Daughter',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);
    }

    /**
     * Create an enrolled patient with at least one recorded dose,
     * so they qualify for the dashboard overdue KPI.
     */
    private function enrolledPatientWithDose(Carbon $dob): Patient
    {
        $patient = $this->enrolledPatient($dob);

        $penta = Vaccine::where('vaccine_code', 'PENTA')->firstOrFail();

        Immunization::create([
            'patient_id' => $patient->id,
            'vaccine_id' => $penta->id,
            'dose_number' => 1,
            'date_given' => $dob->copy()->addDays($penta->schedules->first()->min_age_days)->toDateString(),
        ]);

        return $patient;
    }

    public function test_dashboard_excludes_patients_with_no_doses(): void
    {
        $this->enrolledPatient(now()->subDays(100));

        $count = DashboardQueryService::overdueImmunizations(Carbon::today());

        $this->assertSame(0, $count,
            'Patients with no immunization records should not count as overdue on the dashboard.'
        );
    }

    public function test_dashboard_counts_enrolled_overdue_patients(): void
    {
        $this->enrolledPatientWithDose(now()->subDays(100));

        $count = DashboardQueryService::overdueImmunizations(Carbon::today());

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_dashboard_counts_out_of_window_as_overdue(): void
    {
        $this->enrolledPatientWithDose(now()->subDays(300));

        $count = DashboardQueryService::overdueImmunizations(Carbon::today());

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_dashboard_ignores_age_mismatched_vaccine_categories(): void
    {
        // Infant with birth doses (BCG + Hepa B) whose only overdue vaccines
        // are Adult-category (PNEUMONIA / FLU / PNEUMOCOCCAL). The module
        // queues never surface those for children, so the dashboard KPI must
        // not count them either.
        $patient = $this->enrolledPatient(now()->subDays(28));

        foreach (['BCG', 'HEPA_B_24H'] as $code) {
            $vaccine = Vaccine::where('vaccine_code', $code)->firstOrFail();

            Immunization::create([
                'patient_id' => $patient->id,
                'vaccine_id' => $vaccine->id,
                'dose_number' => 1,
                'date_given' => $patient->date_of_birth->toDateString(),
            ]);
        }

        $count = DashboardQueryService::overdueImmunizations(Carbon::today());

        $this->assertSame(0, $count,
            'Adult-category vaccines must not count an infant as overdue on the dashboard.'
        );
    }
}
