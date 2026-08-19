<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ReferralNotificationTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('permissions')->insert([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'household', 'description' => 'Zone-scoped access', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_referral_created_at_intake_notifies_staff_except_actor(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
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

        $this->assertSame(1, $nurse->notifications()->count());
        $this->assertSame(0, $bhw->notifications()->count());

        $notification = $nurse->notifications()->first();

        $this->assertSame('referral_created', $notification->data['type']);
        $this->assertStringContainsString('Doe, Jane', $notification->data['title']);
        $this->assertStringContainsString('RHU Tagoloan', $notification->data['title']);
        $this->assertSame(route('consultations.show', DB::table('consultations')->where('patient_id', $patientId)->value('id')), $notification->data['url']);
    }

    public function test_referral_created_notifies_in_zone_bhw_but_not_out_of_zone_bhw(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $inZoneBhw = $this->createZoneAssignedUser('BHW In', 1);
        $outOfZoneBhw = $this->createZoneAssignedUser('BHW Out', 2);

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Chest pain',
            'refer_to_higher_facility' => 1,
            'referred_to' => 'Northern Mindanao Medical Center',
            'referral_reasons' => ['specialized_evaluation'],
            'pertinent_history' => 'Chest pain for 3 days',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.0,
            'weight' => 60,
            'height' => 165,
        ])->assertRedirect();

        $this->assertSame(1, $inZoneBhw->notifications()->count());
        $this->assertSame(0, $outOfZoneBhw->notifications()->count());
    }

    public function test_referral_status_change_notifies_staff_except_actor(): void
    {
        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $doctor = $this->createWorkerUser('Doctor');

        $consultationId = $this->startConsultation($bhw, $patientId);
        $referralId = (int) DB::table('outward_referrals')->insertGetId([
            'consultation_id' => $consultationId,
            'destination_facility' => 'RHU Tagoloan',
            'pertinent_history' => 'Fever',
            'actions_taken' => 'Paracetamol',
            'specific_details' => 'Reasons: Fever',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($doctor)->patch("/referrals/{$referralId}/status", [
            'status' => 'completed',
        ])->assertRedirect();

        $this->assertSame(1, $bhw->notifications()->count());
        $this->assertSame(0, $doctor->notifications()->count());

        $notification = $bhw->notifications()->first();

        $this->assertSame('referral_status_changed', $notification->data['type']);
        $this->assertStringContainsString('Referral #'.$referralId.' Completed', $notification->data['title']);
        $this->assertStringContainsString('Doe, Jane', $notification->data['title']);
        $this->assertSame(route('consultations.show', $consultationId), $notification->data['url']);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createClinicalFixture(string $role): array
    {
        $user = $this->createWorkerUser($role);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1', 'created_at' => now(), 'updated_at' => now()]);
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

    private function createZoneAssignedUser(string $name, int $zoneId): User
    {
        $user = $this->createUserWithPermissions(['consultations', 'household']);

        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => $name,
            'last_name' => 'Bhw',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->updateOrInsert(
            ['id' => $zoneId],
            [
                'zone_number' => (string) $zoneId,
                'assigned_worker_id' => $workerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $user;
    }

    private function startConsultation(User $bhw, int $patientId): int
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

        return (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');
    }
}
