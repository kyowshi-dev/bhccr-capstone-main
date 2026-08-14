<?php

namespace Tests\Feature;

use App\Models\HealthWorker;
use App\Models\Household;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalPrenatalFlowsheetTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'maternal',
            'description' => 'Maternal care module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authorizedUser(): User
    {
        $user = $this->createUserWithPermissions(['maternal']);

        HealthWorker::create([
            'user_id' => $user->id,
            'first_name' => 'Midwife',
            'last_name' => 'Test',
            'role' => 'Midwife',
        ]);

        return $user;
    }

    private function pregnancy(): Pregnancy
    {
        $patient = Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Santos'])->id,
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'sex' => 'Female',
            'date_of_birth' => '1992-03-20',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Pedro Santos',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);

        return Pregnancy::create([
            'patient_id' => $patient->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => '2026-01-10',
            'edc' => '2026-10-17',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ]);
    }

    public function test_patient_page_renders_visit_flowsheet_with_linked_vitals(): void
    {
        $this->actingAs($this->authorizedUser());
        $pregnancy = $this->pregnancy();

        $this->post(route('maternal.prenatal.visits.store', $pregnancy->id), [
            'visit_date' => '2026-04-05',
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'chief_complaint' => 'Routine prenatal checkup',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.5,
            'weight' => 60,
            'height' => 160,
            'fundic_height_cm' => 24.5,
            'fetal_heart_tone_bpm' => 140,
            'next_visit_date' => '2026-04-19',
        ])->assertRedirect(route('maternal.prenatal.patient', $pregnancy->patient_id));

        $response = $this->get(route('maternal.prenatal.patient', $pregnancy->patient_id));

        $response->assertOk()
            ->assertSee('Prenatal visits')
            ->assertSee('Record visit')
            ->assertSee('Apr 05, 2026')
            ->assertSee('12 wks')
            ->assertSee('120/80')
            ->assertSee('36.5')
            ->assertSee('24.5')
            ->assertSee('140')
            ->assertSee('Routine prenatal checkup')
            ->assertSee('>60</td>', false)
            ->assertSee('Overdue Apr 19');
    }

    public function test_record_visit_entry_form_has_no_origin_consultation_select(): void
    {
        $this->actingAs($this->authorizedUser());
        $pregnancy = $this->pregnancy();

        $response = $this->get(route('maternal.prenatal.patient', $pregnancy->patient_id));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(
            1,
            preg_match(
                '#<form method="POST" action="http://localhost/pregnancies/\d+/visits"[^>]*>(.*?)</form>#s',
                $html,
                $matches,
            ),
        );

        $this->assertStringNotContainsString('name="consultation_id"', $matches[1]);
        $this->assertStringContainsString('name="visit_date"', $matches[1]);
        $this->assertStringContainsString('name="bp_systolic"', $matches[1]);
        $this->assertStringContainsString('name="fundic_height_cm"', $matches[1]);
    }
}
