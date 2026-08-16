<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ConsultationLifecycleTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insert([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_admission_starts_at_pending_validation_not_doctor_queue(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
            'weight' => 60,
            'height' => 165,
        ])->assertRedirect(route('patients.show', $patientId));

        $consultation = DB::table('consultations')->where('patient_id', $patientId)->first();

        $this->assertNotNull($consultation);
        $this->assertSame('nurse_review', $consultation->status);
        $this->assertNotSame('doctor_review', $consultation->status);
    }

    public function test_nurse_acknowledgement_moves_case_to_doctor_queue(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'bp_systolic' => 110,
            'bp_diastolic' => 70,
            'temperature' => 36.8,
            'weight' => 58,
            'height' => 160,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->actingAs($nurse)->post("/consultations/{$consultationId}/acknowledge-intake")
            ->assertRedirect(route('consultations.show', $consultationId));

        $this->assertSame(
            'doctor_review',
            DB::table('consultations')->where('id', $consultationId)->value('status')
        );
    }

    public function test_clinical_actions_are_blocked_before_doctor_queue(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $doctor = $this->createWorkerUser('Doctor');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'bp_systolic' => 115,
            'bp_diastolic' => 75,
            'temperature' => 36.8,
            'weight' => 62,
            'height' => 168,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');
        $diagnosisId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9',
            'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/diagnosis", [
            'diagnosis_id' => $diagnosisId,
        ])->assertRedirect();

        $this->assertSame(
            'nurse_review',
            DB::table('consultations')->where('id', $consultationId)->value('status')
        );
        $this->assertSame(
            0,
            DB::table('diagnosis_records')->where('consultation_id', $consultationId)->count()
        );
    }

    public function test_bhw_intake_leaves_attending_doctor_empty(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
            'weight' => 60,
            'height' => 165,
        ])->assertRedirect(route('patients.show', $patientId));

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->assertSame(
            null,
            DB::table('consultations')->where('id', $consultationId)->value('attending_doctor_id')
        );
    }

    public function test_doctor_diagnosis_records_licensed_staff_as_attending_doctor(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');
        $doctor = $this->createWorkerUser('Doctor');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
            'weight' => 60,
            'height' => 165,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->actingAs($nurse)->post("/consultations/{$consultationId}/acknowledge-intake");

        $doctorWorkerId = (int) DB::table('health_workers')->where('user_id', $doctor->id)->value('id');
        $diagnosisId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9',
            'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/diagnosis", [
            'diagnosis_id' => $diagnosisId,
            'remarks' => 'Rest and fluids',
        ])->assertRedirect();

        $this->assertSame(
            $doctorWorkerId,
            (int) DB::table('consultations')->where('id', $consultationId)->value('attending_doctor_id')
        );
    }

    public function test_bhw_intake_never_overwrites_attending_doctor(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $doctor = $this->createWorkerUser('Doctor');
        $bhwWorkerId = (int) DB::table('health_workers')->where('user_id', $bhw->id)->value('id');

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $bhwWorkerId,
            'status' => 'doctor_review',
            'attending_doctor_id' => DB::table('health_workers')->where('user_id', $doctor->id)->value('id'),
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vitals')->insert([
            'consultation_id' => $consultationId,
            'phase' => 'triage',
            'captured_by' => $bhwWorkerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($bhw)->post("/consultations/{$consultationId}/refer", [
            'referred_to' => 'RHU Tagoloan',
            'pertinent_history' => 'Ongoing care',
        ])->assertForbidden();

        $this->assertSame(
            (int) DB::table('health_workers')->where('user_id', $doctor->id)->value('id'),
            (int) DB::table('consultations')->where('id', $consultationId)->value('attending_doctor_id')
        );
    }

    public function test_consultations_index_renders_attending_doctor_name(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $doctor = $this->createWorkerUser('Doctor');
        $doctorWorkerId = (int) DB::table('health_workers')->where('user_id', $doctor->id)->value('id');
        $bhwWorkerId = (int) DB::table('health_workers')->where('user_id', $bhw->id)->value('id');

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $bhwWorkerId,
            'attending_doctor_id' => $doctorWorkerId,
            'status' => 'completed',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($bhw)
            ->get(route('consultations.index'))
            ->assertOk()
            ->assertSee('Doctor');
    }

    public function test_chief_complaint_can_be_updated_during_review(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
            'weight' => 60,
            'height' => 165,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->actingAs($bhw)
            ->put("/consultations/{$consultationId}/complaint", [
                'complaint_text' => 'Fever and cough for 3 days',
            ])
            ->assertRedirect(route('consultations.show', $consultationId));

        $this->assertSame(
            'Fever and cough for 3 days',
            DB::table('consultations')->where('id', $consultationId)->value('complaint_text')
        );
    }

    public function test_chief_complaint_cannot_be_updated_after_consultation_is_terminal(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $bhwWorkerId = (int) DB::table('health_workers')->where('user_id', $bhw->id)->value('id');

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $bhwWorkerId,
            'status' => 'completed',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Fever',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($bhw)
            ->put("/consultations/{$consultationId}/complaint", [
                'complaint_text' => 'Tampered complaint',
            ])
            ->assertRedirect();

        $this->assertSame(
            'Fever',
            DB::table('consultations')->where('id', $consultationId)->value('complaint_text')
        );
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createClinicalFixture(string $role): array
    {
        $user = $this->createWorkerUser($role);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('patients')->insertGetId([
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

        return [$user, $patientId];
    }

    private function createWorkerUser(string $role): User
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations']);

        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => $role,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
