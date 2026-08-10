<?php

namespace Tests\Feature;

use App\Models\HealthWorker;
use App\Models\Household;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\PrenatalVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalPrenatalVisitTest extends TestCase
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

    public function test_visit_is_recorded_and_count_derives_from_visits(): void
    {
        $this->actingAs($this->authorizedUser());
        $pregnancy = $this->pregnancy();

        $this->post(route('maternal.prenatal.visits.store', $pregnancy->id), [
            'visit_date' => now()->toDateString(),
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.5,
            'weight' => 60,
            'height' => 160,
            'fundic_height_cm' => 24.5,
            'fetal_heart_tone_bpm' => 140,
            'next_visit_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect(route('maternal.prenatal.patient', $pregnancy->patient_id));

        $visit = PrenatalVisit::where('pregnancy_id', $pregnancy->id)->firstOrFail();

        $this->assertSame(24.5, (float) $visit->fundic_height_cm);
        $this->assertSame(140, (int) $visit->fetal_heart_tone_bpm);
        $this->assertSame(1, $pregnancy->visits()->count());
    }

    public function test_visit_requires_visit_date(): void
    {
        $this->actingAs($this->authorizedUser());
        $pregnancy = $this->pregnancy();

        $this->post(route('maternal.prenatal.visits.store', $pregnancy->id), [
            'visit_date' => '',
        ])->assertSessionHasErrors('visit_date');

        $this->assertDatabaseCount('prenatal_visits', 0);
    }

    public function test_fht_out_of_clinical_range_is_rejected(): void
    {
        $this->actingAs($this->authorizedUser());
        $pregnancy = $this->pregnancy();

        $this->post(route('maternal.prenatal.visits.store', $pregnancy->id), [
            'visit_date' => now()->toDateString(),
            'fetal_heart_tone_bpm' => 240,
        ])->assertSessionHasErrors('fetal_heart_tone_bpm');
    }

    public function test_pregnancy_edit_updates_tt_and_iron(): void
    {
        $this->actingAs($this->authorizedUser());
        $pregnancy = $this->pregnancy();

        $this->put(route('maternal.pregnancies.update', $pregnancy->id), [
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 2,
            'parity' => 1,
            'term' => 1,
            'preterm' => 0,
            'livebirth' => 1,
            'abortion' => 0,
            'lmp' => '2026-01-10',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'tt_date' => now()->toDateString(),
            'iron_taken' => true,
        ])->assertRedirect(route('maternal.prenatal.patient', $pregnancy->patient_id));

        $pregnancy->refresh();

        $this->assertSame(2, $pregnancy->gravidity);
        $this->assertTrue($pregnancy->iron_taken);
        $this->assertNotNull($pregnancy->tt_date);
    }
}
