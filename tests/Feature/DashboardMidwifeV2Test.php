<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\Pregnancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class DashboardMidwifeV2Test extends TestCase
{
    use AssignsRolesAndPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function midwifeUser()
    {
        return $this->createUserWithNamedRole('Midwife', [
            'patients',
            'consultations',
            'maternal',
            'dashboard_handouts',
        ]);
    }

    public function test_feature_flag_renders_v2_dashboard_by_default(): void
    {
        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Service-Tabbed Operational Hub')
            ->assertSee('All Queues')
            ->assertSee('Prenatal')
            ->assertSee('Postnatal')
            ->assertSee('Family Planning')
            ->assertSee('Watchlist');
    }

    public function test_v2_dashboard_displays_kpi_metrics(): void
    {
        $patient = Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Test'])->id,
            'first_name' => 'Jane',
            'last_name' => 'Test',
            'sex' => 'Female',
            'date_of_birth' => '1995-01-01',
            'civil_status' => 'Single',
            'mother_name' => '',
            'spouse_name' => '',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1',
        ]);

        Pregnancy::create([
            'patient_id' => $patient->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => '2026-05-01',
            'edc' => '2026-12-15',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'iron_taken' => false,
        ]);

        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Active Prenatal')
            ->assertSee('Postnatal Due')
            ->assertSee('FP Scheduled');
    }

    public function test_v2_dashboard_includes_quick_intake_dropdown(): void
    {
        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Quick Intake')
            ->assertSee('Log Prenatal Visit')
            ->assertSee('Log Postnatal Visit')
            ->assertSee('Log Family Planning Service');
    }

    public function test_v2_dashboard_renders_results_ready_in_accordion(): void
    {
        $user = $this->midwifeUser();

        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        if ($user->canViewDashboardHandouts()) {
            $response->assertSee('Results Ready');
        }
    }

    public function test_bhw_role_still_sees_bhw_dashboard(): void
    {
        $user = $this->createUserWithPermissions([
            'household',
            'patients',
            'consultations',
            'maternal',
            'dashboard_handouts',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Service-Tabbed Operational Hub');
    }
}
