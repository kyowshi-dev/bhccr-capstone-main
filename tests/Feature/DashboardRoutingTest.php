<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class DashboardRoutingTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    public function test_bhw_role_gets_bhw_dashboard(): void
    {
        $bhw = $this->createUserWithNamedRole('BHW', [
            'household', 'patients', 'consultations', 'reports', 'print_handouts', 'dashboard_handouts_bhw',
        ]);

        $this->actingAs($bhw)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pending Queue')
            ->assertDontSee('Top presenting illnesses');
    }

    public function test_bhw_dashboard_hides_results_ready_without_permission(): void
    {
        $bhw = $this->createUserWithNamedRole('BHW', ['household']);

        $this->actingAs($bhw)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Results Ready');
    }

    public function test_midwife_role_gets_midwife_dashboard(): void
    {
        $midwife = $this->createUserWithNamedRole('Midwife', [
            'patients', 'consultations', 'immunizations', 'reports', 'print_handouts', 'dashboard_handouts_midwife',
        ]);

        $this->actingAs($midwife)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Maternal Dashboard')
            ->assertSee('Active Prenatal')
            ->assertSee('Results Ready');
    }

    public function test_nurse_role_gets_nurse_dashboard(): void
    {
        $nurse = $this->createUserWithNamedRole('Nurse', ['patients', 'consultations', 'medicines']);

        $this->actingAs($nurse)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nurse Dashboard')
            ->assertDontSee('Top Illnesses');
    }

    public function test_doctor_role_gets_doctor_dashboard(): void
    {
        $doctor = $this->createUserWithNamedRole('Doctor', ['patients', 'consultations', 'medicines']);

        $this->actingAs($doctor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Doctor Dashboard')
            ->assertSee('Doctor queue');
    }

    public function test_user_without_health_worker_role_falls_back_to_admin_dashboard(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Top Illnesses');
    }

    public function test_user_with_no_role_falls_back_to_admin_dashboard(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Top Illnesses');
    }

    public function test_health_worker_role_string_is_used_when_role_id_missing(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Nurse',
            'role' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nurse Dashboard');
    }

    public function test_admin_dashboard_on_duty_staff_excludes_disabled_users(): void
    {
        $admin = $this->createUserWithPermissions([]);
        $activeUser = User::factory()->create(['is_active' => true]);
        $disabledUser = User::factory()->create(['is_active' => false]);

        DB::table('health_workers')->insert([
            [
                'user_id' => $activeUser->id,
                'first_name' => 'Active',
                'last_name' => 'Doctor',
                'role' => 'Doctor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $disabledUser->id,
                'first_name' => 'Hidden',
                'last_name' => 'Nurse',
                'role' => 'Nurse',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('On-duty staff')
            ->assertSee('Active Doctor')
            ->assertDontSee('Hidden Nurse');
    }

    public function test_role_permissions_gate_dashboard_handout_panels(): void
    {
        $roleId = DB::table('user_roles')->where('role_name', 'BHW')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', ['household', 'dashboard_handouts_bhw'])->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bhw = User::factory()->create(['role_id' => $roleId]);

        $this->actingAs($bhw)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Results Ready');

        // Remove the handout permission from the role → panel disappears
        DB::table('role_permissions')->where('role_id', $roleId)->delete();
        Cache::forget("user_permissions_{$bhw->id}");

        $this->actingAs(User::find($bhw->id))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Results Ready');
    }
}
