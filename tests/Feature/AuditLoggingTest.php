<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

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

    public function test_medicine_import_logs_audit_entry_with_counts(): void
    {
        $user = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($user);

        $csv = "name,form\nParacetamol,Tablet\nAmoxicillin,Capsule\n";

        $this->post(route('medicines.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('medicines.csv', $csv),
        ])->assertSessionHas('success');

        $log = AuditLog::query()
            ->where('table_name', 'medicines_lookup')
            ->where('action', 'medicines_imported')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(2, $log->new_values['success_count']);
        $this->assertSame(0, $log->new_values['error_count']);
    }

    public function test_medicine_import_fatal_error_logs_audit_entry(): void
    {
        $this->actingAs($this->createUserWithPermissions(['medicines']));

        $this->post(route('medicines.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('medicines.csv', ''),
        ]);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'medicines_lookup')
            ->where('action', 'medicines_import_failed')
            ->count());
    }

    public function test_role_update_logs_audit_entry_with_permission_changes(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $roleId = $this->createRoleWithPermissions(['patients']);

        $extraPermissionId = DB::table('permissions')->insertGetId([
            'name' => 'consultations',
            'description' => 'Test permission: consultations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldPermissionIds = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->put(route('roles.update', $roleId), [
            'role_name' => 'Nurse Updated',
            'permissions' => [$extraPermissionId],
        ])->assertRedirect(route('roles.index'));

        $log = AuditLog::query()
            ->where('table_name', 'user_roles')
            ->where('record_id', $roleId)
            ->where('action', 'role_updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($oldPermissionIds, $log->old_values['permission_ids']);
        $this->assertSame([$extraPermissionId], $log->new_values['permission_ids']);
    }

    public function test_user_audit_entries_never_store_password_hash(): void
    {
        $this->actingUser();

        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $createdLog = AuditLog::query()
            ->where('table_name', 'users')
            ->where('record_id', $user->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($createdLog);
        $this->assertArrayNotHasKey('password', $createdLog->new_values);
        $this->assertStringNotContainsString(
            'secret123',
            json_encode($createdLog->new_values)
        );
    }
}
