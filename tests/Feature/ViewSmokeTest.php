<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ViewSmokeTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['patients', 'consultations', 'immunizations', 'households', 'users', 'zones', 'notifications'] as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm],
                ['description' => $perm, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function createSuperUser(): User
    {
        return $this->createUserWithPermissions([
            'patients', 'consultations', 'immunizations', 'households', 'users', 'zones', 'notifications',
        ]);
    }

    private function createFixture(string $role): array
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations', 'immunizations']);
        $user->profile_photo_path = 'photos/sample.jpg';
        $user->save();

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1, 'family_name_head' => 'Test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId, 'first_name' => 'Jane', 'last_name' => 'Doe',
            'sex' => 'Female', 'date_of_birth' => '1990-01-01', 'civil_status' => 'Single',
            'employment_status' => 'Employed', 'mother_name' => 'Senior', 'spouse_name' => 'N/A',
            'family_relationship' => 'Mother', 'residential_address' => 'Sta. Ana',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('health_workers')->insert([
            'user_id' => $user->id, 'first_name' => 'Test', 'last_name' => $role,
            'role' => $role, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$user, $patientId];
    }

    public function test_changed_views_render(): void
    {
        $super = $this->createSuperUser();
        [$bhw, $patientId] = $this->createFixture('BHW');

        $bhwWorkerId = (int) DB::table('health_workers')->where('user_id', $bhw->id)->value('id');

        DB::table('consultations')->insertGetId([
            'patient_id' => $patientId, 'worker_id' => $bhwWorkerId, 'attending_doctor_id' => $bhwWorkerId,
            'status' => 'completed', 'nature_of_visit' => 'Checkup', 'mode_of_transaction' => 'Walk-in',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($super);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('consultations.index'))->assertOk();
        $this->get(route('referrals.index'))->assertOk();
        $this->get(route('profile.show'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('profile.settings'))->assertOk();
        $this->get(route('notifications.index'))->assertOk();
        $this->get(route('users.edit', $bhw))->assertOk();
        $this->get(route('roles.index'))->assertOk();
        $this->get(route('zones.create'))->assertOk();
        $this->get("/patients/{$patientId}/immunizations")->assertOk();
        $this->get(route('immunizations.checkin', $patientId))->assertOk();
        $this->get(route('immunizations.enroll-infant.create'))->assertOk();
    }
}
