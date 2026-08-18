<?php

namespace Tests\Feature;

use App\Models\PostnatalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ConsultationMaternalLinkTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'maternal', 'description' => 'Maternal care module', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1']);
    }

    public function test_purpose_of_visit_is_persisted_when_starting_consultation(): void
    {
        [$user, $patientId] = $this->createClinicalFixture();

        $this->actingAs($user)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'Prenatal',
            'chief_complaint' => 'Routine checkup',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.0,
            'weight' => 60,
            'height' => 160,
        ])->assertRedirect(route('patients.show', $patientId));

        $this->assertSame(
            'Prenatal',
            DB::table('consultations')->where('patient_id', $patientId)->value('purpose_of_visit')
        );
    }

    public function test_purpose_of_visit_is_required_on_consultation_store(): void
    {
        [$user, $patientId] = $this->createClinicalFixture();

        $this->actingAs($user)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.0,
            'weight' => 60,
            'height' => 160,
        ])->assertSessionHasErrors('purpose_of_visit');
    }

    public function test_prenatal_visit_creation_creates_consultation_in_doctor_queue(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();
        $pregnancyId = $this->createPregnancy($patientId);

        $this->actingAs($user)->post(route('maternal.prenatal.visits.store', $pregnancyId), [
            'visit_date' => now()->toDateString(),
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.5,
            'weight' => 60,
            'height' => 160,
        ])->assertRedirect(route('maternal.prenatal.patient', $patientId));

        $consultation = DB::table('consultations')->where('patient_id', $patientId)->first();

        $this->assertNotNull($consultation);
        $this->assertSame('doctor_review', $consultation->status);
        $this->assertNotNull($consultation->nurse_validated_at);
        $this->assertSame(
            (int) DB::table('health_workers')->where('user_id', $user->id)->value('id'),
            (int) $consultation->nurse_validated_by
        );
    }

    public function test_prenatal_visit_attached_to_todays_consultation_resolves_nurse_review(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();
        $pregnancyId = $this->createPregnancy($patientId);
        $consultationId = $this->createConsultation($patientId);

        $this->actingAs($user)->post(route('maternal.prenatal.visits.store', $pregnancyId), [
            'visit_date' => now()->toDateString(),
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.5,
            'weight' => 60,
            'height' => 160,
        ])->assertRedirect(route('maternal.prenatal.patient', $patientId));

        $this->assertDatabaseCount('consultations', 1);

        $consultation = DB::table('consultations')->where('id', $consultationId)->first();

        $this->assertSame('doctor_review', $consultation->status);
        $this->assertNotNull($consultation->nurse_validated_at);
        $this->assertSame('Prenatal', $consultation->purpose_of_visit);
        $this->assertSame(
            $consultationId,
            (int) DB::table('prenatal_visits')->where('pregnancy_id', $pregnancyId)->value('consultation_id')
        );
    }

    public function test_family_planning_visit_resolves_nurse_review_consultation(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();
        $consultationId = $this->createConsultation($patientId);

        $clientId = DB::table('family_planning_clients')->insertGetId([
            'patient_id' => $patientId,
            'type_of_client' => 'continuing_user',
            'method' => 'Pills',
            'is_active' => true,
            'recorded_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->post("/maternal/family-planning/{$clientId}/visits", [
            'visit_date' => now()->toDateString(),
            'method' => 'Pills',
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.5,
            'weight' => 60,
            'height' => 160,
        ])->assertRedirect(route('maternal.family-planning.patient', $patientId));

        $this->assertSame(
            'doctor_review',
            DB::table('consultations')->where('id', $consultationId)->value('status')
        );
    }

    public function test_postpartum_visit_completion_resolves_nurse_review_consultation(): void
    {
        $user = $this->createMaternalWorker();
        $patientId = $this->createPatient();
        $consultationId = $this->createConsultation($patientId);

        $record = PostnatalRecord::create([
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
        ]);

        $this->actingAs($user)
            ->post(route('maternal.postnatal.complete-visit', $record->id), [
                'slot' => 'postpartum_7d_date',
                'date' => now()->toDateString(),
                'mode_of_transaction' => 'Walk-in',
                'nature_of_visit' => 'New Consultation/Case',
                'bp_systolic' => 120,
                'bp_diastolic' => 80,
                'temperature' => 36.5,
                'weight' => 60,
                'height' => 160,
            ])->assertRedirect(route('maternal.postnatal.patient', $patientId));

        $this->assertSame(
            'doctor_review',
            DB::table('consultations')->where('id', $consultationId)->value('status')
        );
    }

    public function test_consultation_show_lists_linked_maternal_records(): void
    {
        $user = $this->createClinicalFixture()[0];
        $patientId = $this->createPatient();
        $consultationId = $this->createConsultation($patientId);

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

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'consultation_id' => $consultationId,
            'visit_date' => now()->toDateString(),
            'fundic_height_cm' => 24.5,
            'fetal_heart_tone_bpm' => 140,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('consultations.show', $consultationId))
            ->assertOk()
            ->assertSee('Maternal Transactions')
            ->assertSee('Prenatal visits');
    }

    public function test_purpose_of_visit_appears_in_show_page(): void
    {
        [$user, $patientId] = $this->createClinicalFixture();
        $workerId = (int) DB::table('health_workers')->where('user_id', $user->id)->value('id');

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $workerId,
            'status' => 'nurse_review',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'purpose_of_visit' => 'Family Planning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('consultations.show', $consultationId))
            ->assertOk()
            ->assertSee('Family Planning');
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
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'employment_status' => 'Employed',
            'mother_name' => 'Jane Senior',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPregnancy(int $patientId): int
    {
        return DB::table('pregnancies')->insertGetId([
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
    }

    private function createConsultation(int $patientId): int
    {
        $worker = DB::table('health_workers')->orderBy('id')->first();

        return DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $worker->id,
            'status' => 'nurse_review',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
