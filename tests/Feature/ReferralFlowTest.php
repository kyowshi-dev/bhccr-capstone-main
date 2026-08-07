<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ReferralFlowTest extends TestCase
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

    public function test_doctor_referral_creates_outward_referral_and_marks_consultation_referred(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');
        $doctor = $this->createWorkerUser('Doctor');

        $consultationId = $this->startAndAcknowledge($bhw, $nurse, $patientId);

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/refer", [
            'referred_to' => 'Northern Mindanao Medical Center',
            'referral_reasons' => ['specialized_evaluation', 'lack_diagnostics'],
            'referral_reason_details' => 'Needs cardiology workup',
            'pertinent_history' => 'Chest pain for 3 days',
            'actions_taken' => 'Given aspirin, ECG requested',
        ])->assertRedirect(route('consultations.show', $consultationId));

        $referral = DB::table('outward_referrals')->where('consultation_id', $consultationId)->first();

        $this->assertNotNull($referral);
        $this->assertSame('Northern Mindanao Medical Center', $referral->destination_facility);
        $this->assertSame('Chest pain for 3 days', $referral->pertinent_history);
        $this->assertSame('Given aspirin, ECG requested', $referral->actions_taken);
        $this->assertStringContainsString('specialized medical evaluation', (string) $referral->specific_details);
        $this->assertStringContainsString('Needs cardiology workup', (string) $referral->specific_details);
        $this->assertSame('pending', $referral->status);
        $this->assertSame('referred', DB::table('consultations')->where('id', $consultationId)->value('status'));
    }

    public function test_intake_with_referral_creates_outward_referral_row(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'Maternity checkup',
            'chief_complaint' => 'Bleeding',
            'refer_to_higher_facility' => 1,
            'referred_to' => 'RHU Tagoloan',
            'referral_reasons' => ['emergency_trauma'],
            'pertinent_history' => 'Gravid, 34 weeks, spotting',
            'bp_systolic' => 130,
            'bp_diastolic' => 90,
            'temperature' => 36.9,
            'weight' => 62,
            'height' => 158,
        ])->assertRedirect(route('patients.show', $patientId));

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');
        $referral = DB::table('outward_referrals')->where('consultation_id', $consultationId)->first();

        $this->assertNotNull($referral);
        $this->assertSame('RHU Tagoloan', $referral->destination_facility);
        $this->assertStringContainsString('trauma stabilization', (string) $referral->specific_details);
        $this->assertSame('pending', $referral->status);
    }

    public function test_finalize_with_referral_creates_outward_referral_row(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');
        $doctor = $this->createWorkerUser('Doctor');

        $consultationId = $this->startAndAcknowledge($bhw, $nurse, $patientId);

        $diagnosisId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'I10',
            'diagnosis_name' => 'Essential hypertension',
        ]);

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/diagnosis", [
            'diagnosis_id' => $diagnosisId,
        ])->assertRedirect();

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/finalize", [
            'refer_to_higher_facility' => 1,
            'referred_to' => 'Regional Hospital',
            'referral_reasons' => ['lack_medicines'],
            'pertinent_history' => 'Persistent hypertension',
        ])->assertRedirect(route('consultations.show', $consultationId));

        $referral = DB::table('outward_referrals')->where('consultation_id', $consultationId)->first();

        $this->assertNotNull($referral);
        $this->assertSame('Regional Hospital', $referral->destination_facility);
        $this->assertStringContainsString('medicines / vaccines', (string) $referral->specific_details);
        $this->assertSame('referred', DB::table('consultations')->where('id', $consultationId)->value('status'));
    }

    public function test_referral_context_returns_patient_and_vitals_summary(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');
        $doctor = $this->createWorkerUser('Doctor');

        $consultationId = $this->startAndAcknowledge($bhw, $nurse, $patientId);

        $this->actingAs($doctor)
            ->getJson("/consultations/{$consultationId}/referral-context")
            ->assertOk()
            ->assertJsonPath('patient_name', 'Doe, Jane')
            ->assertJsonPath('patient_meta', '36 y/o · Female')
            ->assertJsonStructure(['vitals_summary']);
    }

    public function test_specific_details_builds_readable_text(): void
    {
        $details = ReferralService::specificDetails(
            ['specialized_evaluation'],
            'Second opinion requested'
        );

        $this->assertStringContainsString('specialized medical evaluation', (string) $details);
        $this->assertStringContainsString('Second opinion requested', (string) $details);

        $this->assertNull(ReferralService::specificDetails([], null));
        $this->assertNull(ReferralService::specificDetails([], '   '));
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

    private function startAndAcknowledge(User $bhw, User $nurse, int $patientId): int
    {
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

        return $consultationId;
    }
}
