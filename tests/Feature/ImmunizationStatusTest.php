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
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ImmunizationStatusTest extends TestCase
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

    private function makeInfant(Carbon $dob): Patient
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

    private function vaccine(string $code): Vaccine
    {
        return Vaccine::where('vaccine_code', $code)->firstOrFail();
    }

    private function assertStatusFor(Patient $patient, string $vaccineCode, string $expected): void
    {
        $service = app(ChildImmunizationService::class);
        $this->actingAs($this->createUserWithPermissions(['immunizations']));

        $this->get(route('immunizations.patient', $patient->id))
            ->assertOk()
            ->assertViewHas('statuses', fn ($statuses) => ($statuses[$this->vaccine($vaccineCode)->id] ?? null) === $expected);

        $this->assertSame($expected, $service->statusFor($patient, $this->vaccine($vaccineCode)));
    }

    public function test_status_is_waiting_when_not_yet_eligible(): void
    {
        $this->assertStatusFor($this->makeInfant(now()->subDays(20)), 'PENTA', 'waiting');
    }

    public function test_status_is_overdue_when_age_window_start_passed(): void
    {
        $this->assertStatusFor($this->makeInfant(now()->subDays(100)), 'PENTA', 'overdue');
    }

    public function test_status_is_completed_when_all_doses_given(): void
    {
        $dob = now()->subDays(200);
        $patient = $this->makeInfant($dob);
        $pentA = $this->vaccine('PENTA');

        foreach ([1, 2, 3] as $dose) {
            Immunization::create([
                'patient_id' => $patient->id,
                'vaccine_id' => $pentA->id,
                'dose_number' => $dose,
                'date_given' => $dob->copy()->addDays($pentA->schedules->get($dose - 1)->min_age_days)->toDateString(),
            ]);
        }

        $this->assertStatusFor($patient, 'PENTA', 'completed');
    }

    public function test_status_is_out_of_window_when_completion_cannot_fit(): void
    {
        $this->assertStatusFor($this->makeInfant(now()->subDays(300)), 'ROTA', 'out_of_window');
    }

    public function test_status_is_no_show_when_latest_record_flagged(): void
    {
        $patient = $this->makeInfant(now()->subDays(100));

        app(ChildImmunizationService::class)->markNoShow($patient, $this->vaccine('PENTA'));

        $this->assertStatusFor($patient, 'PENTA', 'no_show');
    }
}
