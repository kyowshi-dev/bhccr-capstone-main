<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultationLifecycleTest extends TestCase
{
    use RefreshDatabase;

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
            'chief_complaint' => 'Fever',
            'temperature' => 37.5,
        ])->assertRedirect(route('patients.show', $patientId));

        $consultation = DB::table('consultations')->where('patient_id', $patientId)->first();

        $this->assertNotNull($consultation);
        $this->assertSame('pending_validation', $consultation->status);
        $this->assertNotSame('pending_doctor', $consultation->status);
    }

    public function test_nurse_acknowledgement_moves_case_to_doctor_queue(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'temperature' => 36.8,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->actingAs($nurse)->post("/consultations/{$consultationId}/acknowledge-intake")
            ->assertRedirect(route('consultations.show', $consultationId));

        $this->assertSame(
            'pending_doctor',
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
            'temperature' => 36.8,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');
        $diagnosisId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9',
            'diagnosis_name' => 'Acute upper respiratory infection',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/diagnosis", [
            'diagnosis_id' => $diagnosisId,
        ])->assertRedirect();

        $this->assertSame(
            'pending_validation',
            DB::table('consultations')->where('id', $consultationId)->value('status')
        );
        $this->assertSame(
            0,
            DB::table('diagnosis_records')->where('consultation_id', $consultationId)->count()
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $patientId];
    }

    private function createWorkerUser(string $role): User
    {
        $user = User::factory()->create();
        $user->permissions()->sync(
            DB::table('permissions')->whereIn('name', ['patients', 'consultations'])->pluck('id')
        );

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
