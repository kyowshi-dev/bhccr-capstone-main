<?php

namespace Tests\Feature;

use App\Models\FamilyPlanningClient;
use App\Models\Household;
use App\Models\OutwardReferral;
use App\Models\Patient;
use App\Models\User;
use App\Services\FamilyPlanningReportService;
use App\Services\ImmunizationReportService;
use App\Services\MaternalCareReportService;
use App\Services\NcdReportService;
use App\Services\ReferralReportService;
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

    private function healthWorkerFor(User $user): int
    {
        return DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'role' => 'Midwife',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function patient(string $lastName = 'Reyes'): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => $lastName])->id,
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

    private function consultation(int $patientId, int $workerId): int
    {
        return DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $workerId,
            'status' => 'completed',
            'mode_of_transaction' => 'Walk-in',
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

    public function test_maternal_care_report_renders_indicators(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = DB::table('pregnancies')->insertGetId([
            'patient_id' => $patient->id,
            'status' => 'active',
            'gravidity' => 1,
            'parity' => 0,
            'lmp' => now()->subMonths(4)->toDateString(),
            'syphilis_result' => 'negative',
            'iron_taken' => 1,
            'tt_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->toDateString(),
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
            'child_last_name' => 'Reyes',
            'child_first_name' => 'Baby',
            'child_sex' => 'M',
            'postpartum_24h_date' => now()->toDateString(),
            'postpartum_7d_date' => now()->toDateString(),
            'postpartum_14d_date' => now()->toDateString(),
            'postpartum_28d_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.maternal-care', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee('New prenatal clients')
            ->assertSee('Maternal Care Report');
    }

    public function test_maternal_care_report_counts_prenatal_and_delivery_indicators(): void
    {
        $user = $this->authorizedUser();
        $patient = $this->patient();

        $pregnancyId = DB::table('pregnancies')->insertGetId([
            'patient_id' => $patient->id,
            'status' => 'active',
            'gravidity' => 1,
            'parity' => 0,
            'lmp' => now()->subMonths(4)->toDateString(),
            'syphilis_result' => 'negative',
            'iron_taken' => 1,
            'tt_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = MaternalCareReportService::query(now()->month, now()->year, null, $user);

        $this->assertSame(1, $report['newPrenatalClients']);
        $this->assertSame(1, $report['prenatalVisits']);
        $this->assertSame(1, $report['ttDoses']);
        $this->assertSame(1, $report['ironSupplemented']);
    }

    public function test_immunization_report_tallies_doses_by_vaccine(): void
    {
        $user = $this->authorizedUser();
        $patient = $this->patient();

        $vaccineId = DB::table('vaccines_lookup')->insertGetId([
            'vaccine_code' => 'BCG_TEST',
            'vaccine_name' => 'BCG Test',
            'category' => 'Child',
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $vaccineId,
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = ImmunizationReportService::query(now()->month, now()->year, null, $user);

        $this->assertSame(1, $report['totalDoses']);
        $this->assertSame(1, $report['childDoses']);
        $this->assertSame(0, $report['adultDoses']);
        $this->assertSame(1, $report['doses']->first()->doses);
    }

    public function test_family_planning_report_counts_acceptors_per_method(): void
    {
        $user = $this->authorizedUser();
        $patient = $this->patient();

        $client = FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Injectable',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('family_planning_visits')->insert([
            'client_id' => $client->id,
            'visit_date' => now()->toDateString(),
            'method' => 'Injectable',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = FamilyPlanningReportService::query(now()->month, now()->year, null, $user);

        $this->assertSame(1, $report['totalNew']);
        $this->assertSame(1, $report['totalVisits']);

        $row = $report['rows']->firstWhere('method', 'Injectable');
        $this->assertSame(1, $row->new_acceptors);
        $this->assertSame(1, $row->visits);
    }

    public function test_ncd_report_counts_hypertension_patients_from_diagnoses(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $diagnosisId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'I10',
            'diagnosis_name' => 'Essential hypertension',
        ]);

        $consultationId = $this->consultation($patient->id, $workerId);

        DB::table('diagnosis_records')->insert([
            'consultation_id' => $consultationId,
            'diagnosis_id' => $diagnosisId,
            'diagnosed_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = NcdReportService::query(now()->month, now()->year, null, $user);

        $this->assertSame(1, $report['totalPatients']);
        $this->assertSame(1, $report['totalConsultations']);
        $this->assertSame(1, $report['rows']->firstWhere('key', 'Hypertension')->patients_seen);
        $this->assertSame(1, $report['rows']->firstWhere('key', 'Hypertension')->registry_patients);
    }

    public function test_referral_report_counts_outward_and_incoming(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        $outwardPatient = $this->patient('Dela Cruz');
        $outwardConsultationId = $this->consultation($outwardPatient->id, $workerId);

        DB::table('outward_referrals')->insert([
            'consultation_id' => $outwardConsultationId,
            'destination_facility' => 'NMMC',
            'pertinent_history' => 'Chest pain',
            'actions_taken' => 'Referred',
            'specific_details' => 'Cardiology workup',
            'status' => OutwardReferral::STATUS_COMPLETED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $incomingPatient = $this->patient('Santos');
        DB::table('consultations')->insert([
            'patient_id' => $incomingPatient->id,
            'worker_id' => $workerId,
            'status' => 'completed',
            'mode_of_transaction' => 'Walk-in',
            'referred_from' => 'RHU Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = ReferralReportService::query(now()->month, now()->year, null, $user);

        $this->assertSame(1, $report['totalOutward']);
        $this->assertSame(1, $report['outwardByDestination']->first()->total);
        $this->assertSame(1, $report['outwardByStatus']->first()->total);
        $this->assertSame(1, $report['totalInward']);
        $this->assertSame('RHU Tagoloan', $report['inwardBySource']->first()->source);
    }

    public function test_zone_scoped_user_only_sees_assigned_zone_data(): void
    {
        $user = $this->authorizedUser();
        $patient = $this->patient('Zone1');

        $vaccineId = DB::table('vaccines_lookup')->insertGetId([
            'vaccine_code' => 'BCG_TEST2',
            'vaccine_name' => 'BCG Test 2',
            'category' => 'Child',
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $vaccineId,
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = ImmunizationReportService::query(now()->month, now()->year, null, $user);

        $this->assertSame(1, $report['totalDoses']);
    }
}
