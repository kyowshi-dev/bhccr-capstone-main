<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Household;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\User;
use App\Models\WatchlistEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class MaternalQueueControllerTest extends TestCase
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

    private function activePregnancy(Patient $patient, string $lmp): Pregnancy
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
            'edc' => Carbon::parse($lmp)->addDays(280)->toDateString(),
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'iron_taken' => false,
            'recorded_by' => null,
        ]);
    }

    private function midwifeUser(): User
    {
        $user = $this->createUserWithPermissions([
            'maternal', 'consultations',
        ]);

        HealthWorker::create([
            'user_id' => $user->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'role' => 'Midwife',
        ]);

        return $user->fresh();
    }

    public function test_queue_partial_returns_html_for_all_tab(): void
    {
        $user = $this->midwifeUser();
        $patient = $this->mother();
        $this->activePregnancy($patient, '2026-05-01');

        $response = $this->actingAs($user)
            ->get(route('maternal.queue-partial', ['tab' => 'all']))
            ->assertOk();

        $response->assertSee($patient->last_name);
    }

    public function test_queue_partial_returns_html_for_prenatal_tab(): void
    {
        $user = $this->midwifeUser();
        $patient = $this->mother('Torres');
        $this->activePregnancy($patient, '2026-06-01');

        $response = $this->actingAs($user)
            ->get(route('maternal.queue-partial', ['tab' => 'prenatal']))
            ->assertOk();

        $response->assertSee($patient->last_name);
    }

    public function test_queue_partial_returns_empty_state_for_empty_tab(): void
    {
        $user = $this->midwifeUser();

        $response = $this->actingAs($user)
            ->get(route('maternal.queue-partial', ['tab' => 'fp']))
            ->assertOk();

        $response->assertSee('All caught up');
    }

    public function test_queue_partial_renders_general_watchlist_entry(): void
    {
        $user = $this->midwifeUser();
        $worker = $user->healthWorker;
        $patient = $this->mother('Castro');

        WatchlistEntry::create([
            'patient_id' => $patient->id,
            'program_type' => 'general',
            'reason_code' => 'follow_up_needed',
            'flagged_by' => $worker->id,
            'flagged_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('maternal.queue-partial', ['tab' => 'watchlist']))
            ->assertOk()
            ->assertSee($patient->last_name);
    }

    public function test_queue_partial_requires_permission(): void
    {
        $user = $this->createUserWithPermissions(['consultations']);

        $this->actingAs($user)
            ->get(route('maternal.queue-partial', ['tab' => 'all']))
            ->assertStatus(403);
    }

    public function test_add_to_watchlist_creates_entry(): void
    {
        $user = $this->midwifeUser();
        $patient = $this->mother('Reyes');

        $response = $this->actingAs($user)
            ->postJson("/api/maternal/watchlist/{$patient->id}", [
                'program_type' => 'prenatal',
                'reason_code' => 'preeclampsia_risk',
                'notes' => 'Blood pressure elevated',
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'entry_id']);

        $this->assertDatabaseHas('watchlist_entries', [
            'patient_id' => $patient->id,
            'program_type' => 'prenatal',
            'reason_code' => 'preeclampsia_risk',
            'resolved_at' => null,
        ]);
    }

    public function test_add_to_watchlist_validates_input(): void
    {
        $user = $this->midwifeUser();
        $patient = $this->mother();

        $this->actingAs($user)
            ->postJson("/api/maternal/watchlist/{$patient->id}", [])
            ->assertStatus(422);
    }

    public function test_remove_from_watchlist_resolves_entry(): void
    {
        $user = $this->midwifeUser();
        $worker = $user->healthWorker;
        $patient = $this->mother('Dela Cruz');

        $entry = WatchlistEntry::create([
            'patient_id' => $patient->id,
            'program_type' => 'postnatal',
            'reason_code' => 'missed_14d_visit',
            'flagged_by' => $worker->id,
            'flagged_at' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/maternal/watchlist/{$entry->id}")
            ->assertOk();

        $entry->refresh();
        $this->assertNotNull($entry->resolved_at);
    }

    public function test_link_pregnancy_attaches_to_consultation(): void
    {
        $user = $this->midwifeUser();
        $worker = $user->healthWorker;
        $patient = $this->mother('Villanueva');
        $pregnancy = $this->activePregnancy($patient, '2026-05-01');

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => 'triage',
            'mode_of_transaction' => 'Walk-in',
            'purpose_of_visit' => 'Prenatal',
        ]);

        $this->actingAs($user)
            ->postJson("/api/maternal/link-pregnancy/{$consultation->id}", [
                'pregnancy_id' => $pregnancy->id,
            ])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Consultation linked to pregnancy.']);

        $consultation->refresh();
        $this->assertEquals($pregnancy->id, $consultation->pregnancy_id);
    }

    public function test_link_pregnancy_rejects_wrong_patient(): void
    {
        $user = $this->midwifeUser();
        $worker = $user->healthWorker;
        $patient = $this->mother('Cruz');
        $otherPatient = $this->mother('Lim');
        $pregnancy = $this->activePregnancy($otherPatient, '2026-05-01');

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => 'triage',
            'mode_of_transaction' => 'Walk-in',
            'purpose_of_visit' => 'Prenatal',
        ]);

        $this->actingAs($user)
            ->postJson("/api/maternal/link-pregnancy/{$consultation->id}", [
                'pregnancy_id' => $pregnancy->id,
            ])
            ->assertStatus(404);
    }

    public function test_link_pregnancy_requires_permission(): void
    {
        $user = $this->createUserWithPermissions(['consultations']);
        $patient = $this->mother();

        $worker = HealthWorker::create([
            'user_id' => $user->id,
            'first_name' => 'Temp',
            'last_name' => 'Worker',
            'role' => 'BHW',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => 'triage',
            'mode_of_transaction' => 'Walk-in',
        ]);

        $this->actingAs($user)
            ->postJson("/api/maternal/link-pregnancy/{$consultation->id}", [
                'pregnancy_id' => 1,
            ])
            ->assertStatus(403);
    }
}
