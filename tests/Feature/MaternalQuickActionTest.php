<?php

namespace Tests\Feature;

use App\Models\Pregnancy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalQuickActionTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'patients', 'description' => 'Access to Patients', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'maternal', 'description' => 'Maternal care', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1']);
    }

    public function test_light_register_pregnancy_with_lmp_only(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'register',
                'lmp' => '2026-01-10',
            ])->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, DB::table('pregnancies')->where('patient_id', $patientId)->count());
        $this->assertSame(
            '2026-10-17',
            Carbon::parse(DB::table('pregnancies')->where('patient_id', $patientId)->value('edc'))->toDateString()
        );
    }

    public function test_register_with_risk_flags_persists_them(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'register',
                'lmp' => '2026-03-01',
                'risk_flags' => ['hypertension', 'previous_csection'],
            ])->assertOk();

        $risk = DB::table('pregnancies')->where('patient_id', $patientId)->value('risk_flags');
        $decoded = json_decode($risk, true);
        $this->assertCount(2, $decoded);
        $this->assertContains('hypertension', $decoded);
    }

    public function test_duplicate_active_pregnancy_returns_422(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'register',
                'lmp' => '2026-01-10',
            ])->assertOk();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'register',
                'lmp' => '2026-05-01',
            ])->assertStatus(422);
    }

    public function test_prenatal_visit_creates_consultation_and_visit(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();
        $pregnancyId = DB::table('pregnancies')->insertGetId([
            'patient_id' => $patientId,
            'status' => 'active',
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'log_prenatal_visit',
                'visit_date' => now()->toDateString(),
                'bp_systolic' => 130,
                'bp_diastolic' => 85,
                'weight' => 65.5,
                'temperature' => 36.8,
                'fundic_height_cm' => 22,
                'fetal_heart_tone_bpm' => 140,
                'next_visit_date' => now()->addDays(28)->toDateString(),
            ])->assertOk()
            ->assertJson(['success' => true]);

        $consultation = DB::table('consultations')
            ->where('patient_id', $patientId)
            ->where('purpose_of_visit', 'Prenatal')
            ->first();
        $this->assertNotNull($consultation);

        $vital = DB::table('vitals')->where('consultation_id', $consultation->id)->first();
        $this->assertNotNull($vital);
        $this->assertSame(130, (int) $vital->bp_systolic);

        $visit = DB::table('prenatal_visits')->where('pregnancy_id', $pregnancyId)->first();
        $this->assertNotNull($visit);
        $this->assertSame($consultation->id, (int) $visit->consultation_id);
    }

    public function test_prenatal_visit_requires_active_pregnancy(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'log_prenatal_visit',
                'visit_date' => now()->toDateString(),
            ])->assertStatus(422);
    }

    public function test_postpartum_fills_open_slot(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        DB::table('postnatal_records')->insert([
            'patient_id' => $patientId,
            'pregnancy_outcome' => 'live_birth',
            'place_delivered' => 'health_center',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->subDays(2)->toDateString(),
            'delivery_time' => '08:30',
            'breastfeeding_date' => now()->subDays(2)->toDateString(),
            'breastfeeding_time' => '10:00',
            'child_last_name' => 'Doe',
            'child_first_name' => 'Baby',
            'child_sex' => 'M',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'log_postpartum',
                'visit_date' => now()->toDateString(),
                'bp_systolic' => 110,
                'bp_diastolic' => 70,
            ])->assertOk()
            ->assertJson(['success' => true]);

        $record = DB::table('postnatal_records')->where('patient_id', $patientId)->first();
        $this->assertNotNull($record->postpartum_24h_date);
        $this->assertNull($record->postpartum_7d_date);
    }

    public function test_postpartum_rejects_without_postnatal_record(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'log_postpartum',
                'visit_date' => now()->toDateString(),
            ])->assertStatus(422);
    }

    public function test_guest_cannot_access_quick_action(): void
    {
        $patientId = $this->createPatient();

        $this->postJson("/maternal/quick/{$patientId}", [
            'action' => 'register',
            'lmp' => '2026-01-10',
        ])->assertUnauthorized();
    }

    public function test_user_without_maternal_permission_is_blocked(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $patientId = $this->createPatient();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'register',
                'lmp' => '2026-01-10',
            ])->assertStatus(403);
    }

    public function test_risk_flagged_pregnancy_appears_in_watchlist(): void
    {
        $patientId = $this->createPatient();
        DB::table('pregnancies')->insert([
            'patient_id' => $patientId,
            'status' => 'active',
            'gravidity' => 2,
            'parity' => 1,
            'term' => 1,
            'preterm' => 0,
            'livebirth' => 1,
            'abortion' => 0,
            'lmp' => '2026-03-01',
            'edc' => '2026-12-05',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'risk_flags' => json_encode(['hypertension', 'diabetes']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $count = Pregnancy::where('status', 'active')
            ->whereNotNull('risk_flags')
            ->get()
            ->filter(fn ($p) => ! empty($p->risk_flags))
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_watchlist_excludes_pregnancies_without_risk_flags(): void
    {
        $patientId = $this->createPatient();
        DB::table('pregnancies')->insert([
            'patient_id' => $patientId,
            'status' => 'active',
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => '2026-03-01',
            'edc' => '2026-12-05',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'risk_flags' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $count = Pregnancy::where('status', 'active')
            ->whereNotNull('risk_flags')
            ->get()
            ->filter(fn ($p) => ! empty($p->risk_flags))
            ->count();

        $this->assertSame(0, $count);
    }

    public function test_family_planning_visit_creates_record(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        DB::table('family_planning_clients')->insert([
            'patient_id' => $patientId,
            'type_of_client' => 'continuing_user',
            'method' => 'Pills',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'log_fp_visit',
                'visit_date' => now()->toDateString(),
                'method' => 'Injectable',
                'next_visit_date' => now()->addMonths(3)->toDateString(),
            ])->assertOk()
            ->assertJson(['success' => true]);

        $visit = DB::table('family_planning_visits')->latest('id')->first();
        $this->assertNotNull($visit);
        $this->assertSame('Injectable', $visit->method);
    }

    public function test_family_planning_rejects_without_active_client(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();

        $this->actingAs($user)
            ->postJson("/maternal/quick/{$patientId}", [
                'action' => 'log_fp_visit',
                'visit_date' => now()->toDateString(),
                'method' => 'Pills',
            ])->assertStatus(422);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createClinicalFixture(): array
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations']);
        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'role' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $this->createPatient()];
    }

    private function createMaternalWorker(): User
    {
        $user = $this->createUserWithPermissions(['patients', 'maternal']);
        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Maternal',
            'role' => 'Midwife',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function createPatient(): int
    {
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'sex' => 'Female',
            'date_of_birth' => '1992-05-10',
            'civil_status' => 'Married',
            'employment_status' => 'Employed',
            'mother_name' => 'Elena Santos',
            'spouse_name' => 'Juan Santos',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
