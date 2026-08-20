<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class PrescriptionEntryTest extends TestCase
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

    public function test_doctor_adds_prescription_with_complete_sig(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $doctor = $this->createWorkerUser('Doctor');
        $medicineId = $this->createMedicine('Amoxicillin 500mg', 'capsule');

        $response = $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $medicineId,
            'dosage' => '1 cap',
            'route' => 'PO',
            'frequency' => 'TID (3 times daily)',
            'duration' => '7 days',
            'quantity' => 21,
            'instructions' => 'Take after meals',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('prescriptions', [
            'consultation_id' => $consultationId,
            'medicine_id' => $medicineId,
            'dosage' => '1 cap',
            'route' => 'PO',
            'frequency' => 'TID (3 times daily)',
            'duration' => '7 days',
            'quantity' => 21,
            'instructions' => 'Take after meals',
        ]);
    }

    public function test_prescription_requires_duration_frequency_and_quantity(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $doctor = $this->createWorkerUser('Doctor');
        $medicineId = $this->createMedicine('Paracetamol 500mg', 'tablet');

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $medicineId,
            'dosage' => '1 tab',
            'route' => 'PO',
        ])->assertSessionHasErrors(['frequency', 'duration', 'quantity']);

        $this->assertSame(0, DB::table('prescriptions')->where('consultation_id', $consultationId)->count());
    }

    public function test_prescription_rejects_invalid_route(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $doctor = $this->createWorkerUser('Doctor');
        $medicineId = $this->createMedicine('Metformin 500mg', 'tablet');

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $medicineId,
            'dosage' => '1 tab',
            'route' => 'Through the nose',
            'frequency' => 'BID (2x daily)',
            'duration' => '30 days',
            'quantity' => 60,
        ])->assertSessionHasErrors('route');
    }

    public function test_duplicate_medicine_on_same_consultation_is_blocked(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $doctor = $this->createWorkerUser('Doctor');
        $medicineId = $this->createMedicine('Losartan 50mg', 'tablet');

        $payload = [
            'medicine_id' => $medicineId,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'OD (once daily)',
            'duration' => '30 days',
            'quantity' => 30,
        ];

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", $payload)
            ->assertRedirect();

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", $payload)
            ->assertSessionHasErrors('medicine_id');

        $this->assertSame(1, DB::table('prescriptions')->where('consultation_id', $consultationId)->count());
    }

    public function test_different_medicine_on_same_consultation_is_allowed(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $doctor = $this->createWorkerUser('Doctor');
        $first = $this->createMedicine('Paracetamol 500mg', 'tablet');
        $second = $this->createMedicine('Amoxicillin 500mg', 'capsule');

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $first,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID (3 times daily)',
            'duration' => '7 days',
            'quantity' => 21,
        ])->assertRedirect();

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $second,
            'dosage' => '1 cap',
            'route' => 'PO',
            'frequency' => 'BID (2x daily)',
            'duration' => '7 days',
            'quantity' => 14,
        ])->assertRedirect();

        $this->assertSame(2, DB::table('prescriptions')->where('consultation_id', $consultationId)->count());
    }

    public function test_non_doctor_cannot_add_prescription(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $nurse = $this->createWorkerUser('Nurse');
        $medicineId = $this->createMedicine('Paracetamol 500mg', 'tablet');

        $this->actingAs($nurse)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $medicineId,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID (3 times daily)',
            'duration' => '7 days',
            'quantity' => 21,
        ])->assertForbidden();

        $this->assertSame(0, DB::table('prescriptions')->where('consultation_id', $consultationId)->count());
    }

    public function test_non_doctor_cannot_add_prescription_from_edit_page(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $nurse = $this->createWorkerUser('Nurse');

        $this->actingAs($nurse)->postJson("/consultations/{$consultationId}/edit-prescription", [
            'medicine_name' => 'Custom Syrup',
            'dosage' => '5 mL',
            'route' => 'PO',
            'frequency' => 'BID (2x daily)',
            'duration' => '5 days',
            'quantity' => 10,
        ])->assertForbidden();

        $this->assertSame(0, DB::table('prescriptions')->where('consultation_id', $consultationId)->count());
    }

    public function test_non_doctor_cannot_update_or_delete_prescription(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $doctor = $this->createWorkerUser('Doctor');
        $nurse = $this->createWorkerUser('Nurse');
        $medicineId = $this->createMedicine('Paracetamol 500mg', 'tablet');

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $medicineId,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID (3 times daily)',
            'duration' => '7 days',
            'quantity' => 21,
        ]);

        $prescriptionId = (int) DB::table('prescriptions')->where('consultation_id', $consultationId)->value('id');

        $this->actingAs($nurse)->putJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}", [
            'medicine_name' => 'Paracetamol 500mg',
            'dosage' => '2 tabs',
            'route' => 'PO',
            'frequency' => 'BID (2x daily)',
            'duration' => '5 days',
            'quantity' => 20,
        ])->assertForbidden();

        $this->actingAs($nurse)->deleteJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}")
            ->assertForbidden();

        $this->assertSame(1, DB::table('prescriptions')->where('consultation_id', $consultationId)->count());
    }

    public function test_doctor_can_update_prescription_route_and_instructions(): void
    {
        [$bhw, $patientId, $consultationId] = $this->createDoctorReviewFixture();
        $doctor = $this->createWorkerUser('Doctor');
        $medicineId = $this->createMedicine('Ibuprofen 400mg', 'tablet');

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/prescription", [
            'medicine_id' => $medicineId,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID (3 times daily)',
            'duration' => '5 days',
            'quantity' => 15,
        ]);

        $prescriptionId = (int) DB::table('prescriptions')->where('consultation_id', $consultationId)->value('id');

        $this->actingAs($doctor)->putJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}", [
            'medicine_name' => 'Ibuprofen 400mg',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'QID (4x daily)',
            'duration' => '3 days',
            'quantity' => 12,
            'instructions' => 'Take with food',
        ])->assertOk();

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescriptionId,
            'route' => 'PO',
            'frequency' => 'QID (4x daily)',
            'duration' => '3 days',
            'quantity' => 12,
            'instructions' => 'Take with food',
        ]);
    }

    /**
     * @return array{0: User, 1: int, 2: int}
     */
    private function createDoctorReviewFixture(): array
    {
        $bhw = $this->createWorkerUser('BHW');

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

        $bhwWorkerId = (int) DB::table('health_workers')->where('user_id', $bhw->id)->value('id');

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $bhwWorkerId,
            'status' => 'doctor_review',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$bhw, $patientId, $consultationId];
    }

    private function createMedicine(string $name, string $form): int
    {
        return DB::table('medicines_lookup')->insertGetId([
            'name' => $name,
            'form' => $form,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
