<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class EloquentModelsTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'zones', 'description' => 'Access to Zones module', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
    }

    private function createHouseholdFixture(int $zoneId = 1): int
    {
        return DB::table('households')->insertGetId([
            'zone_id' => $zoneId,
            'family_name_head' => 'Dela Cruz',
            'contact_number' => '09171234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPatientFixture(int $householdId): int
    {
        return DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => '1985-05-10',
            'civil_status' => 'Married',
            'employment_status' => 'Employed',
            'mother_name' => 'Maria',
            'spouse_name' => 'Juana',
            'family_relationship' => 'Father',
            'residential_address' => '1 Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_patients_index_renders_with_last_visit_via_eloquent(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $householdId = $this->createHouseholdFixture();
        $patientId = $this->createPatientFixture($householdId);

        $worker = User::factory()->create();
        DB::table('health_workers')->insert([
            'user_id' => $worker->id,
            'first_name' => 'Nurse',
            'last_name' => 'Santos',
            'role' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('consultations')->insert([
            'patient_id' => $patientId,
            'worker_id' => 1,
            'status' => 'completed',
            'complaint_text' => 'Fever',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('patients.index', ['sort' => 'last_visit', 'dir' => 'desc']))
            ->assertOk()
            ->assertSee('Juan')
            ->assertSee('Dela Cruz')
            ->assertSee('09171234567')
            ->assertSee(now()->subDay()->format('Y-m-d'));
    }

    public function test_patients_show_renders_history_with_worker_name_accessors(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $householdId = $this->createHouseholdFixture();
        $patientId = $this->createPatientFixture($householdId);

        $worker = User::factory()->create();
        DB::table('health_workers')->insert([
            'user_id' => $worker->id,
            'first_name' => 'Nurse',
            'last_name' => 'Santos',
            'role' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('consultations')->insert([
            'patient_id' => $patientId,
            'worker_id' => 1,
            'status' => 'completed',
            'nature_of_visit' => 'Checkup',
            'complaint_text' => 'Fever',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('patients.show', $patientId))
            ->assertOk()
            ->assertSee('Zone 1')
            ->assertSee('Checkup')
            ->assertSee('Nurse Santos');
    }

    public function test_zones_index_renders_worker_name_via_relation(): void
    {
        $user = $this->createUserWithPermissions(['zones']);

        $worker = User::factory()->create();
        DB::table('health_workers')->insert([
            'user_id' => $worker->id,
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insert([
            'id' => 2,
            'zone_number' => '2',
            'assigned_worker_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('zones.index'))
            ->assertOk()
            ->assertSee('Clara, Maria');
    }

    public function test_zones_show_renders_household_and_patient_counts(): void
    {
        $user = $this->createUserWithPermissions(['zones']);
        $householdId = $this->createHouseholdFixture(1);
        $this->createPatientFixture($householdId);

        $this->actingAs($user)
            ->get(route('zones.show', 1))
            ->assertOk()
            ->assertSee('Zone 1');
    }

    public function test_zone_store_persists_via_eloquent(): void
    {
        $user = $this->createUserWithPermissions(['zones']);

        $this->actingAs($user)
            ->post(route('zones.store'), [
                'zone_number' => '9',
            ])
            ->assertRedirect(route('zones.index'));

        $this->assertDatabaseHas('zones', ['zone_number' => '9']);
    }
}
