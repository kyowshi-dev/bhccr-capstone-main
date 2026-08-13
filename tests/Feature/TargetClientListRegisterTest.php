<?php

namespace Tests\Feature;

use App\Models\FamilyPlanningClient;
use App\Models\Household;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class TargetClientListRegisterTest extends TestCase
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

    private function maternalUser()
    {
        return $this->createUserWithPermissions(['maternal', 'patients']);
    }

    private function mother(string $lastName = 'Test'): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => $lastName])->id,
            'first_name' => 'Rosa',
            'last_name' => $lastName,
            'sex' => 'Female',
            'date_of_birth' => '1991-11-02',
            'civil_status' => 'Married',
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

    public function test_postnatal_register_defaults_to_active_cases(): void
    {
        $active = $this->mother('Aquino');
        $pregnancy = $this->activePregnancy($active, '2025-11-01');
        $pregnancy->update(['status' => Pregnancy::STATUS_DELIVERED]);

        $activeRecord = PostnatalRecord::create([
            'patient_id' => $active->id,
            'pregnancy_id' => $pregnancy->id,
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

        $completed = $this->mother('Reyes');
        $completedPregnancy = $this->activePregnancy($completed, '2025-10-01');
        $completedPregnancy->update(['status' => Pregnancy::STATUS_DELIVERED]);

        PostnatalRecord::create([
            'patient_id' => $completed->id,
            'pregnancy_id' => $completedPregnancy->id,
            'pregnancy_outcome' => 'live_birth',
            'prenatal_visits_completed' => 1,
            'place_delivered' => 'home',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->subDays(60)->toDateString(),
            'delivery_time' => '08:30:00',
            'breastfeeding_date' => now()->subDays(60)->toDateString(),
            'breastfeeding_time' => '10:00:00',
            'postpartum_24h_date' => now()->subDays(59)->toDateString(),
            'postpartum_7d_date' => now()->subDays(53)->toDateString(),
            'postpartum_14d_date' => now()->subDays(46)->toDateString(),
            'postpartum_28d_date' => now()->subDays(32)->toDateString(),
            'child_sex' => 'F',
            'recorded_by' => null,
        ]);

        $user = $this->maternalUser();

        $default = $this->actingAs($user)
            ->get(route('maternal.postnatal.index'))
            ->assertOk();

        $default->assertSee($active->last_name);
        $default->assertDontSee($completed->last_name);

        $all = $this->actingAs($user)
            ->get(route('maternal.postnatal.index', ['status' => 'all']))
            ->assertOk();

        $all->assertSee($completed->last_name);
        $all->assertViewHas('records', fn ($records) => $records->count() === 2);
    }

    public function test_fp_register_defaults_to_active_clients(): void
    {
        $active = $this->mother('Diaz');
        FamilyPlanningClient::create([
            'patient_id' => $active->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'IUD',
            'is_active' => true,
        ]);

        $inactive = $this->mother('Lim');
        FamilyPlanningClient::create([
            'patient_id' => $inactive->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Pills',
            'is_active' => false,
        ]);

        $user = $this->maternalUser();

        $default = $this->actingAs($user)
            ->get(route('maternal.family-planning.index'))
            ->assertOk();

        $default->assertSee($active->last_name);
        $default->assertDontSee($inactive->last_name);

        $all = $this->actingAs($user)
            ->get(route('maternal.family-planning.index', ['status' => 'all']))
            ->assertOk();

        $all->assertSee($inactive->last_name);
        $all->assertViewHas('clients', fn ($clients) => $clients->count() === 2);
    }

    public function test_prenatal_register_shows_high_risk_badge(): void
    {
        $highRisk = $this->mother('Castro');
        $this->activePregnancy($highRisk, '2026-06-01')
            ->update(['risk_flags' => ['hypertension']]);

        $normal = $this->mother('Garcia');
        $this->activePregnancy($normal, '2026-06-15');

        $user = $this->maternalUser();

        $this->actingAs($user)
            ->get(route('maternal.prenatal.index'))
            ->assertOk()
            ->assertSee('High risk');
    }
}
