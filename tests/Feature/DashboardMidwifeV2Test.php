<?php

namespace Tests\Feature;

use App\Models\FamilyPlanningClient;
use App\Models\Household;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\PrenatalVisit;
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

    private function mother(string $lastName = 'Test'): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => $lastName])->id,
            'first_name' => 'Jane',
            'last_name' => $lastName,
            'sex' => 'Female',
            'date_of_birth' => '1995-01-01',
            'civil_status' => 'Single',
            'mother_name' => '',
            'spouse_name' => '',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1',
        ]);
    }

    private function activePregnancy(Patient $patient, string $lmp = '2026-05-01'): Pregnancy
    {
        return Pregnancy::create([
            'patient_id' => $patient->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => $lmp,
            'edc' => '2026-12-15',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'iron_taken' => false,
        ]);
    }

    public function test_midwife_sees_barangay_overview_header(): void
    {
        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Maternal & Family Planning Register')
            ->assertDontSee('Service-Tabbed Operational Hub')
            ->assertDontSee('All Queues')
            ->assertDontSee('Watchlist');
    }

    public function test_midwife_dashboard_displays_kpi_metrics(): void
    {
        $patient = $this->mother();
        $this->activePregnancy($patient);

        FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Pills',
            'is_active' => true,
        ]);

        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Active Pregnancies')
            ->assertSee('Postnatal Mothers')
            ->assertSee('FP Clients')
            ->assertSee('Follow-ups Due This Week');
    }

    public function test_midwife_dashboard_includes_resident_search_and_quick_links(): void
    {
        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Search resident by name')
            ->assertSee('Open Prenatal Register')
            ->assertSee('Open FP Register')
            ->assertSee('Register New Resident');
    }

    public function test_midwife_dashboard_counts_active_pregnancies(): void
    {
        $this->activePregnancy($this->mother('Garcia'));
        $this->activePregnancy($this->mother('Reyes'));

        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('activePregnancies', 2);
    }

    public function test_midwife_dashboard_counts_fp_clients(): void
    {
        $patient = $this->mother('Diaz');
        FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'IUD',
            'is_active' => true,
        ]);

        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('fpClients', 1);
    }

    public function test_midwife_dashboard_counts_postnatal_mothers_and_follow_ups(): void
    {
        $delivered = $this->mother('Aquino');
        $deliveredPregnancy = $this->activePregnancy($delivered, '2025-11-01');
        $deliveredPregnancy->update(['status' => Pregnancy::STATUS_DELIVERED]);

        PostnatalRecord::create([
            'patient_id' => $delivered->id,
            'pregnancy_id' => $deliveredPregnancy->id,
            'pregnancy_outcome' => 'live_birth',
            'prenatal_visits_completed' => 1,
            'place_delivered' => 'health_center',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->subDays(3)->toDateString(),
            'delivery_time' => '08:30:00',
            'breastfeeding_date' => now()->subDays(3)->toDateString(),
            'breastfeeding_time' => '10:00:00',
            'child_sex' => 'M',
            'recorded_by' => null,
        ]);

        $dueMother = $this->mother('Cruz');
        $duePregnancy = $this->activePregnancy($dueMother, '2026-06-01');
        PrenatalVisit::create([
            'pregnancy_id' => $duePregnancy->id,
            'visit_date' => now()->subDays(7)->toDateString(),
            'next_visit_date' => now()->addDays(3)->toDateString(),
            'recorded_by' => null,
        ]);

        $user = $this->midwifeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('postnatalMothers', 1)
            ->assertViewHas('followUpsDue', 2);
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
            ->assertDontSee('Maternal & Family Planning Register');
    }
}
