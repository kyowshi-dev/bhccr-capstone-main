<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        return $user;
    }

    private function createPatient(User $user): Patient
    {
        $zone = Zone::query()->create(['zone_number' => '1']);

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $zone->id,
            'family_name_head' => 'Dela Cruz',
        ]);

        return Patient::query()->create([
            'household_id' => $householdId,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => null,
            'suffix' => null,
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'birth_place' => 'Sta. Ana',
            'blood_type' => 'O+',
            'civil_status' => 'Single',
            'educational_attainment' => 'College',
            'employment_status' => 'Employed',
            'mother_name' => 'Maria',
            'spouse_name' => 'Mercedes',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1',
            'is_philhealth_member' => 'n',
            'status_type' => null,
            'philhealth_no' => null,
            'membership_category' => null,
            'is_pcb_member' => 'n',
            'has_4ps' => false,
            'has_nhts' => false,
        ]);
    }

    private function createHealthWorker(User $user): HealthWorker
    {
        return HealthWorker::query()->create([
            'user_id' => $user->id,
            'first_name' => 'Nurse',
            'last_name' => 'Bacani',
            'role' => 'Nurse',
        ]);
    }

    public function test_patient_creation_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'created')
            ->count());
    }

    public function test_patient_update_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $patient->update(['first_name' => 'Pedro']);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_user_creation_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $user = User::factory()->create();

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'users')
            ->where('record_id', $user->id)
            ->where('action', 'created')
            ->count());
    }

    public function test_user_update_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $user = User::factory()->create();

        $user->update(['username' => 'updated_username']);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'users')
            ->where('record_id', $user->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_consultation_creation_logs_exactly_one_audit_entry(): void
    {
        $user = $this->actingUser();

        $patient = $this->createPatient($user);
        $worker = $this->createHealthWorker($user);

        $consultation = Consultation::query()->create([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => 'triage',
            'is_locked' => false,
            'mode_of_transaction' => 'walk-in',
        ]);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'consultations')
            ->where('record_id', $consultation->id)
            ->where('action', 'created')
            ->count());
    }

    public function test_consultation_update_logs_exactly_one_audit_entry(): void
    {
        $user = $this->actingUser();

        $patient = $this->createPatient($user);
        $worker = $this->createHealthWorker($user);

        $consultation = Consultation::query()->create([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => 'triage',
            'is_locked' => false,
            'mode_of_transaction' => 'walk-in',
        ]);

        $consultation->update(['status' => 'completed']);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'consultations')
            ->where('record_id', $consultation->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_noop_update_does_not_log_audit_entry(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $patient->update(['first_name' => 'Juan']);

        $this->assertSame(0, AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'updated')
            ->count());
    }
}
