<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\ChildImmunizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChildImmunizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChildImmunizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChildImmunizationService::class);
    }

    private function makeHousehold(): Household
    {
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1', 'created_at' => now(), 'updated_at' => now()]);

        return Household::create([
            'zone_id' => 1,
            'family_name_head' => 'Dela Cruz',
        ]);
    }

    private function makePatient(Carbon $dob): Patient
    {
        return Patient::create([
            'household_id' => $this->makeHousehold()->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => $dob->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria',
            'spouse_name' => 'Juan',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1 Sta. Ana, Tagoloan',
            'is_immunization_enrolled' => true,
        ]);
    }

    private function vaccine(string $code): Vaccine
    {
        return Vaccine::where('vaccine_code', $code)->firstOrFail();
    }

    public function test_age_parts_converts_years_months_days(): void
    {
        $dob = now()->subYears(1)->subMonths(2)->subDays(5);

        $parts = ChildImmunizationService::ageParts($this->makePatient($dob));

        $this->assertSame(['years' => 1, 'months' => 2, 'days' => 5], $parts);
    }

    public function test_status_is_waiting_when_not_yet_eligible(): void
    {
        $patient = $this->makePatient(now()->subDays(20));

        $this->assertSame('waiting', $this->service->statusFor($patient, $this->vaccine('PENTA')));
    }

    public function test_status_is_overdue_when_first_dose_window_start_passed(): void
    {
        $patient = $this->makePatient(now()->subDays(100));

        $this->assertSame('overdue', $this->service->statusFor($patient, $this->vaccine('PENTA')));
    }

    public function test_status_is_overdue_when_gap_elapsed_since_last_dose(): void
    {
        $patient = $this->makePatient(now()->subDays(120));
        $pentA = $this->vaccine('PENTA');

        Immunization::create([
            'patient_id' => $patient->id,
            'vaccine_id' => $pentA->id,
            'dose_number' => 1,
            'date_given' => now()->subDays(50)->toDateString(),
        ]);

        $this->assertSame('overdue', $this->service->statusFor($patient, $pentA));
    }

    public function test_status_is_completed_when_all_doses_given(): void
    {
        $patient = $this->makePatient(now()->subDays(200));
        $pentA = $this->vaccine('PENTA');

        foreach ([1, 2, 3] as $dose) {
            Immunization::create([
                'patient_id' => $patient->id,
                'vaccine_id' => $pentA->id,
                'dose_number' => $dose,
                'date_given' => now()->subDays(200 - $dose * 10)->toDateString(),
            ]);
        }

        $this->assertSame('completed', $this->service->statusFor($patient, $pentA));
    }

    public function test_status_is_out_of_window_when_completion_cannot_fit(): void
    {
        $patient = $this->makePatient(now()->subDays(300));

        $this->assertSame('out_of_window', $this->service->statusFor($patient, $this->vaccine('ROTA')));
    }

    public function test_status_is_no_show_when_latest_record_flagged(): void
    {
        $patient = $this->makePatient(now()->subDays(100));
        $pentA = $this->vaccine('PENTA');

        $this->service->markNoShow($patient, $pentA);

        $this->assertSame('no_show', $this->service->statusFor($patient, $pentA));
    }

    public function test_eligibility_returns_too_early_with_earliest_date(): void
    {
        $patient = $this->makePatient(now()->subDays(30));

        $result = $this->service->eligibility($patient, $this->vaccine('PENTA'));

        $this->assertSame('too_early', $result['state']);
        $this->assertTrue($result['earliest_date']->isSameDay($patient->date_of_birth->copy()->addDays(42)));
    }

    public function test_eligibility_allows_overdue_without_override(): void
    {
        $patient = $this->makePatient(now()->subDays(100));

        $result = $this->service->eligibility($patient, $this->vaccine('PENTA'));

        $this->assertSame('overdue_allowed', $result['state']);
        $this->assertFalse($result['requires_override']);
    }

    public function test_eligibility_flags_out_of_window_override(): void
    {
        $patient = $this->makePatient(now()->subDays(300));

        $result = $this->service->eligibility($patient, $this->vaccine('ROTA'));

        $this->assertSame('out_of_window', $result['state']);
        $this->assertTrue($result['requires_override']);
    }

    public function test_projected_completion_chains_gap_from_next_dose(): void
    {
        $patient = $this->makePatient(now()->subDays(100));

        $completion = $this->service->projectedCompletionDate($patient, $this->vaccine('ROTA'));

        $this->assertTrue($completion->isSameDay($patient->date_of_birth->copy()->addDays(128)));
    }

    public function test_administer_creates_record_and_computes_next_due(): void
    {
        $patient = $this->makePatient(now()->subDays(70));
        $pentA = $this->vaccine('PENTA');

        $record = $this->service->administer($patient, $pentA, [
            'temp_recorded' => '36.8',
            'notes' => 'OK',
        ]);

        $this->assertDatabaseHas('immunization_records', [
            'id' => $record->id,
            'patient_id' => $patient->id,
            'vaccine_id' => $pentA->id,
            'dose_number' => 1,
            'temp_recorded' => '36.80',
            'no_show' => 0,
        ]);
        $this->assertTrue(
            $this->service->nextDoseDate($patient, $pentA, 1)?->isSameDay(now()->addDays(28))
        );
    }

    public function test_administer_auto_advances_dose_number(): void
    {
        $patient = $this->makePatient(now()->subDays(120));
        $pentA = $this->vaccine('PENTA');

        $this->service->administer($patient, $pentA, ['temp_recorded' => '36.5', 'date_given' => now()->subDays(40)]);
        $second = $this->service->administer($patient, $pentA, ['temp_recorded' => '36.5']);

        $this->assertSame(2, $second->dose_number);
    }

    public function test_administer_rejects_next_dose_before_interval_elapses(): void
    {
        $patient = $this->makePatient(now()->subDays(120));
        $pentA = $this->vaccine('PENTA');

        $this->service->administer($patient, $pentA, ['temp_recorded' => '36.5', 'date_given' => now()->subDays(10)->toDateString()]);

        $this->expectException(ValidationException::class);
        $this->service->administer($patient, $pentA, ['temp_recorded' => '36.5']);
    }

    public function test_status_is_waiting_when_interval_since_last_dose_not_elapsed(): void
    {
        $patient = $this->makePatient(now()->subDays(120));
        $pentA = $this->vaccine('PENTA');

        $this->service->administer($patient, $pentA, ['temp_recorded' => '36.5', 'date_given' => now()->subDays(10)->toDateString()]);

        $this->assertSame('waiting', $this->service->statusFor($patient, $pentA));
    }

    public function test_administer_requires_temp_when_schedule_requires_it(): void
    {
        $patient = $this->makePatient(now()->subDays(70));

        $this->expectException(ValidationException::class);
        $this->service->administer($patient, $this->vaccine('PENTA'), []);
    }

    public function test_administer_rejects_too_early(): void
    {
        $patient = $this->makePatient(now()->subDays(30));

        $this->expectException(ValidationException::class);
        $this->service->administer($patient, $this->vaccine('PENTA'), ['temp_recorded' => '36.5']);
    }

    public function test_administer_rejects_out_of_window_without_override_reason(): void
    {
        $patient = $this->makePatient(now()->subDays(300));

        $this->expectException(ValidationException::class);
        $this->service->administer($patient, $this->vaccine('ROTA'), ['temp_recorded' => '36.5']);
    }

    public function test_administer_allows_out_of_window_with_override_reason(): void
    {
        $patient = $this->makePatient(now()->subDays(300));

        $record = $this->service->administer($patient, $this->vaccine('ROTA'), [
            'temp_recorded' => '36.5',
            'override_reason' => 'Catch-up per physician advice',
        ]);

        $this->assertDatabaseHas('immunization_records', ['id' => $record->id, 'dose_number' => 1]);
    }

    public function test_administer_rejects_series_already_complete(): void
    {
        $patient = $this->makePatient(now()->subDays(200));
        $pentA = $this->vaccine('PENTA');

        foreach ([1, 2, 3] as $dose) {
            Immunization::create([
                'patient_id' => $patient->id,
                'vaccine_id' => $pentA->id,
                'dose_number' => $dose,
                'date_given' => now()->subDays(200 - $dose * 10)->toDateString(),
            ]);
        }

        $this->expectException(ValidationException::class);
        $this->service->administer($patient, $pentA, ['temp_recorded' => '36.5']);
    }

    public function test_group_guard_blocks_alternative_birth_dose(): void
    {
        $patient = $this->makePatient(now()->subDays(10));

        $this->service->administer($patient, $this->vaccine('HEPA_B_24H'), ['temp_recorded' => '36.5']);

        $this->expectException(ValidationException::class);
        $this->service->administer($patient, $this->vaccine('HEPA_B_GT24'), ['temp_recorded' => '36.5']);
    }

    public function test_group_guard_allows_late_dose_without_birth_dose(): void
    {
        $patient = $this->makePatient(now()->subDays(90));

        $record = $this->service->administer($patient, $this->vaccine('HEPA_B_GT24'), ['temp_recorded' => '36.5']);

        $this->assertSame(1, $record->dose_number);
    }

    public function test_giving_birth_dose_marks_sibling_hep_b_variants_completed(): void
    {
        $patient = $this->makePatient(now()->subDays(100));

        $this->service->administer($patient, $this->vaccine('HEPA_B_24H'), ['temp_recorded' => '36.5']);

        $this->assertSame('completed', $this->service->statusFor($patient, $this->vaccine('HEPA_B_GT24')));
    }

    public function test_giving_birth_dose_clears_hep_b_variants_from_overdue_queue(): void
    {
        $patient = $this->makePatient(now()->subDays(100));

        $hepCodes = ['HEPA_B_24H', 'HEPA_B_GT24'];

        $overdueBefore = $this->service->queue('overdue')
            ->filter(fn (array $entry) => in_array($entry['vaccine']->vaccine_code, $hepCodes, true));
        $this->assertNotEmpty($overdueBefore);

        $this->service->administer($patient, $this->vaccine('HEPA_B_24H'), ['temp_recorded' => '36.5']);

        $overdueAfter = $this->service->queue('overdue')
            ->filter(fn (array $entry) => in_array($entry['vaccine']->vaccine_code, $hepCodes, true));
        $this->assertTrue($overdueAfter->isEmpty());
    }

    public function test_giving_late_dose_marks_sibling_hep_b_variants_completed(): void
    {
        $patient = $this->makePatient(now()->subDays(100));

        $this->service->administer($patient, $this->vaccine('HEPA_B_GT24'), ['temp_recorded' => '36.5']);

        $this->assertSame('completed', $this->service->statusFor($patient, $this->vaccine('HEPA_B_24H')));
    }

    public function test_mark_no_show_appends_missed_event_and_clear_resolves_it(): void
    {
        $patient = $this->makePatient(now()->subDays(100));
        $pentA = $this->vaccine('PENTA');

        $event = $this->service->markNoShow($patient, $pentA);

        $this->assertSame('missed', $event->event_type);
        $this->assertDatabaseHas('immunization_status_events', ['id' => $event->id, 'event_type' => 'missed']);
        $this->assertDatabaseCount('immunization_records', 0);
        $this->assertSame('no_show', $this->service->statusFor($patient, $pentA));

        $this->service->clearNoShow($patient, $pentA);

        $this->assertSame('overdue', $this->service->statusFor($patient, $pentA));
    }

    public function test_administer_after_no_show_uses_same_dose_number(): void
    {
        $patient = $this->makePatient(now()->subDays(100));
        $pentA = $this->vaccine('PENTA');

        $this->service->markNoShow($patient, $pentA);
        $record = $this->service->administer($patient, $pentA, ['temp_recorded' => '36.5']);

        $this->assertSame(1, $record->dose_number);
        $this->assertDatabaseHas('immunization_status_events', [
            'patient_id' => $patient->id,
            'vaccine_id' => $pentA->id,
            'event_type' => 'attended',
        ]);
        $this->assertSame('waiting', $this->service->statusFor($patient, $pentA));
    }
}
