<?php

namespace Tests\Feature;

use App\Models\FamilyPlanningClient;
use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use App\Services\MchEpiFpReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class FhisReportsTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'reports',
            'description' => 'Access to reports module',
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
        return $this->createUserWithPermissions(['reports']);
    }

    private function healthWorkerFor(User $user, string $lastName = 'Worker'): int
    {
        return DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => $lastName,
            'role' => 'Midwife',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function patient(string $lastName = 'Reyes', int $zoneId = 1): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => $zoneId, 'family_name_head' => $lastName])->id,
            'first_name' => 'Liza',
            'last_name' => $lastName,
            'sex' => 'Female',
            'date_of_birth' => '1995-08-15',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Marco Reyes',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);
    }

    private function filters(?string $from = null, ?string $to = null): array
    {
        return [
            'from' => $from ?? now()->startOfMonth()->toDateString(),
            'to' => $to ?? now()->endOfMonth()->toDateString(),
        ];
    }

    private function pregnancy(Patient $patient, int $workerId, string $createdAt = 'now'): int
    {
        $timestamp = $createdAt === 'now' ? now() : $createdAt;

        return DB::table('pregnancies')->insertGetId([
            'patient_id' => $patient->id,
            'status' => 'active',
            'gravidity' => 1,
            'parity' => 0,
            'lmp' => now()->subMonths(4)->toDateString(),
            'syphilis_result' => 'negative',
            'iron_taken' => 1,
            'tt_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function vaccineId(string $code = 'BCG_TEST'): int
    {
        return DB::table('vaccines_lookup')->insertGetId([
            'vaccine_code' => $code,
            'vaccine_name' => 'BCG Test',
            'category' => 'Child',
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_reports_index_requires_reports_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_mch_epi_fp_report_renders_register_with_patient_worker_and_date(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = $this->pregnancy($patient, $workerId);

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $this->vaccineId(),
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $client = FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Injectable',
            'is_active' => true,
            'recorded_by' => $workerId,
        ]);

        DB::table('family_planning_visits')->insert([
            'client_id' => $client->id,
            'visit_date' => now()->toDateString(),
            'method' => 'Injectable',
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.mch-epi-fp', $this->filters()))
            ->assertOk()
            ->assertSee('Maternal, EPI & Family Planning Report')
            ->assertSee(now()->startOfMonth()->format('m/d/Y').' - '.now()->endOfMonth()->format('m/d/Y'))
            ->assertSee(now()->format('m/d/Y'))
            ->assertSee('Reyes')
            ->assertSee('Test Worker')
            ->assertSee('Prenatal registration')
            ->assertSee('Prenatal visit')
            ->assertSee('BCG Test 1')
            ->assertSee('New Acceptor - Injectable')
            ->assertSee('Visit - Injectable');
    }

    public function test_mch_epi_fp_report_summaries_count_all_programs(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = $this->pregnancy($patient, $workerId);

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('postnatal_records')->insert([
            'patient_id' => $patient->id,
            'pregnancy_id' => $pregnancyId,
            'pregnancy_outcome' => 'live_birth',
            'place_delivered' => 'health_center',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->toDateString(),
            'delivery_time' => '09:30:00',
            'breastfeeding_date' => now()->toDateString(),
            'breastfeeding_time' => '10:00:00',
            'postpartum_24h_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $this->vaccineId(),
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_OTHERS,
            'method' => 'Pills',
            'is_active' => true,
            'recorded_by' => $workerId,
        ]);

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertSame(1, $report['summaries']['maternal']['newPrenatalClients']);
        $this->assertSame(1, $report['summaries']['maternal']['prenatalVisits']);
        $this->assertSame(1, $report['summaries']['maternal']['totalDeliveries']);
        $this->assertSame(1, $report['summaries']['maternal']['postpartum24h']);
        $this->assertSame(1, $report['summaries']['epi']['totalDoses']);
        $this->assertSame(1, $report['summaries']['epi']['childDoses']);
        $this->assertSame(0, $report['summaries']['epi']['adultDoses']);
        $this->assertSame(1, $report['summaries']['fp']['totalOthers']);
        $this->assertSame(0, $report['summaries']['fp']['totalNew']);
    }

    public function test_mch_epi_fp_report_respects_date_range(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = $this->pregnancy($patient, $workerId, now()->subMonth());

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->subMonth()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]);

        $currentMonth = MchEpiFpReportService::query($this->filters(), $user);
        $this->assertSame(0, $currentMonth['totalRows']);

        $wideRange = MchEpiFpReportService::query($this->filters(now()->subMonths(2)->startOfMonth()->toDateString()), $user);
        $this->assertSame(2, $wideRange['totalRows']);
        $this->assertSame(1, $wideRange['summaries']['maternal']['prenatalVisits']);
    }

    public function test_mch_epi_fp_program_filter_limits_register_and_summaries(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $this->pregnancy($patient, $workerId);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $this->vaccineId(),
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $epiOnly = MchEpiFpReportService::query($this->filters() + ['program' => 'epi'], $user);

        $this->assertSame(1, $epiOnly['totalRows']);
        $this->assertSame('EPI Immunization', $epiOnly['rows']->first()->program_label);
        $this->assertNull($epiOnly['summaries']['maternal']);
        $this->assertNull($epiOnly['summaries']['fp']);
        $this->assertNotNull($epiOnly['summaries']['epi']);
        $this->assertSame(1, $epiOnly['programCounts']['epi']);
        $this->assertSame(0, $epiOnly['programCounts']['maternal']);
        $this->assertSame(0, $epiOnly['programCounts']['fp']);
    }

    public function test_mch_epi_fp_report_zone_scoping_hides_other_zones(): void
    {
        $user = $this->createUserWithPermissions(['reports', 'household']);
        $workerId = $this->healthWorkerFor($user);

        DB::table('zones')->insertOrIgnore([
            'id' => 2,
            'zone_number' => 'Zone 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $ownPatient = $this->patient('OwnZone', 1);
        $otherPatient = $this->patient('OtherZone', 2);
        $vaccineId = $this->vaccineId('BCG_SCOPED');

        DB::table('immunization_records')->insert([
            'patient_id' => $otherPatient->id,
            'vaccine_id' => $vaccineId,
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $ownPatient->id,
            'vaccine_id' => $vaccineId,
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertSame(1, $report['totalRows']);
        $this->assertSame(1, $report['summaries']['epi']['totalDoses']);
        $this->assertStringContainsString('OwnZone', $report['rows']->first()->patient_name);
    }

    public function test_mch_epi_fp_search_filters_register_by_patient_name(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $delosSantos = $this->patient('Delos Santos');
        $reyes = $this->patient('Reyes');

        $this->pregnancy($delosSantos, $workerId);
        $this->pregnancy($reyes, $workerId);

        $search = MchEpiFpReportService::query($this->filters() + ['search' => 'rey'], $user);

        $this->assertSame(1, $search['totalRows']);
        $this->assertStringContainsString('Reyes', $search['rows']->first()->patient_name);

        $noMatch = MchEpiFpReportService::query($this->filters() + ['search' => 'zzz'], $user);

        $this->assertSame(0, $noMatch['totalRows']);
    }

    public function test_legacy_report_routes_redirect_to_merged_report(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get('/reports/maternal-care?month='.now()->month.'&year='.now()->year)
            ->assertRedirect(route('reports.mch-epi-fp', $this->filters()));

        $this->actingAs($user)
            ->get('/reports/immunization')
            ->assertRedirect(route('reports.mch-epi-fp'));

        $this->actingAs($user)
            ->get('/reports/family-planning/download')
            ->assertRedirect(route('reports.mch-epi-fp.download'));

        $this->actingAs($user)
            ->get('/reports/ncd')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/reports/referrals')
            ->assertNotFound();
    }
}
