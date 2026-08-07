<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Services\MaternalQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaternalDashboardTest extends TestCase
{
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

    private function mother(string $lastName = 'Garcia'): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => $lastName])->id,
            'first_name' => 'Rosa',
            'last_name' => $lastName,
            'sex' => 'Female',
            'date_of_birth' => '1991-11-02',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Jose '.$lastName,
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);
    }

    private function activePregnancy(Patient $patient, string $lmp, ?string $edc = null): Pregnancy
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
            'edc' => $edc ?? Carbon::parse($lmp)->addDays(280)->toDateString(),
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ]);
    }

    private function postnatal(Patient $patient, string $deliveryDate, array $slots = [], array $dangerSigns = []): PostnatalRecord
    {
        return PostnatalRecord::create([
            'patient_id' => $patient->id,
            'pregnancy_outcome' => PostnatalRecord::OUTCOME_LIVE_BIRTH,
            'place_delivered' => 'home',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => $deliveryDate,
            'delivery_time' => '10:30',
            'breastfeeding_date' => $deliveryDate,
            'breastfeeding_time' => '11:00',
            'danger_signs_mother' => $dangerSigns,
            'child_last_name' => 'Garcia',
            'child_first_name' => 'Baby',
            'child_sex' => 'F',
            ...$slots,
        ]);
    }

    public function test_prenatal_registrant_count_only_counts_active(): void
    {
        $this->activePregnancy($this->mother('Santos'), '2026-01-10');
        $this->activePregnancy($this->mother('Reyes'), '2026-02-10');

        $closed = $this->activePregnancy($this->mother('Lopez'), '2025-06-01');
        $closed->update(['status' => Pregnancy::STATUS_CLOSED]);

        $this->assertSame(2, app(MaternalQueryService::class)->prenatalRegistrants());
    }

    public function test_due_this_month_counts_edcs_within_month(): void
    {
        $today = Carbon::create(2026, 8, 6);
        $this->activePregnancy($this->mother('Santos'), '2025-10-01', '2026-08-15');
        $this->activePregnancy($this->mother('Reyes'), '2025-10-01', '2026-08-30');
        $this->activePregnancy($this->mother('Lopez'), '2025-12-01', '2026-10-01');

        $this->assertSame(2, app(MaternalQueryService::class)->dueThisMonth($today));
    }

    public function test_postnatal_due_counts_missing_past_slots_once_per_record(): void
    {
        $today = Carbon::create(2026, 8, 6);

        $this->postnatal($this->mother('Santos'), '2026-07-01');
        $this->postnatal($this->mother('Reyes'), '2026-08-05');
        $this->postnatal($this->mother('Lopez'), '2026-07-01', ['postpartum_24h_date' => '2026-07-02', 'postpartum_7d_date' => '2026-07-08']);
        $this->postnatal($this->mother('Tan'), '2026-08-01', ['postpartum_24h_date' => '2026-08-01', 'postpartum_7d_date' => '2026-08-08', 'postpartum_14d_date' => '2026-08-15', 'postpartum_28d_date' => '2026-08-29']);
        $this->postnatal($this->mother('Cruz'), '2026-08-06', [
            'postpartum_24h_date' => '2026-08-06',
            'postpartum_7d_date' => '2026-08-13',
            'postpartum_14d_date' => '2026-08-20',
            'postpartum_28d_date' => '2026-09-03',
        ]);

        $service = app(MaternalQueryService::class);

        $this->assertSame(3, $service->postnatalDue($today));
    }

    public function test_high_risk_referrals_counts_mothers_with_danger_signs(): void
    {
        $this->postnatal($this->mother('Santos'), '2026-08-01', [], ['Fever']);
        $this->postnatal($this->mother('Reyes'), '2026-08-01');
        $this->postnatal($this->mother('Lopez'), '2026-08-01', [], ['Vaginal bleeding']);

        $this->assertSame(2, app(MaternalQueryService::class)->highRiskReferrals());
    }
}
