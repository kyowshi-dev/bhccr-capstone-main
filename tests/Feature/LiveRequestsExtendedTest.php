<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\Pregnancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class LiveRequestsExtendedTest extends TestCase
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

    public function test_live_requests_without_flag_returns_standard_response(): void
    {
        Config::set('features.maternal_tabbed_hub', false);

        $user = $this->createUserWithPermissions(['consultations']);

        $response = $this->actingAs($user)
            ->get(route('consultations.live-requests'))
            ->assertOk()
            ->assertJsonStructure(['hasRequest']);

        $response->assertJsonMissing(['queue_counts']);
        $response->assertJsonMissing(['queue_version_hash']);
    }

    public function test_live_requests_with_flag_includes_queue_data_for_midwife(): void
    {
        Config::set('features.maternal_tabbed_hub', true);

        $user = $this->createUserWithPermissions([
            'consultations',
            'maternal',
            'maternal.view_queues',
        ]);

        $patient = Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Test'])->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
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

        $response = $this->actingAs($user)
            ->get(route('consultations.live-requests'))
            ->assertOk()
            ->assertJsonStructure([
                'hasRequest',
                'queue_counts' => ['all', 'prenatal', 'postnatal', 'fp', 'watchlist'],
                'queue_version_hash',
            ]);

        $data = $response->json();
        $this->assertIsArray($data['queue_counts']);
        $this->assertIsString($data['queue_version_hash']);
        $this->assertGreaterThanOrEqual(1, $data['queue_counts']['prenatal']);
    }

    public function test_live_requests_with_flag_excludes_queue_data_for_user_without_permission(): void
    {
        Config::set('features.maternal_tabbed_hub', true);

        $user = $this->createUserWithPermissions(['consultations']);

        $response = $this->actingAs($user)
            ->get(route('consultations.live-requests'))
            ->assertOk()
            ->assertJsonStructure(['hasRequest']);

        $response->assertJsonMissing(['queue_counts']);
    }

    public function test_live_requests_unauthenticated(): void
    {
        $this->get(route('consultations.live-requests'))
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Unauthenticated.']);
    }

    public function test_live_requests_hash_changes_when_data_changes(): void
    {
        Config::set('features.maternal_tabbed_hub', true);

        $user = $this->createUserWithPermissions([
            'consultations',
            'maternal',
            'maternal.view_queues',
        ]);

        $response1 = $this->actingAs($user)
            ->get(route('consultations.live-requests'));
        $hash1 = $response1->json('queue_version_hash');

        $patient = Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'New'])->id,
            'first_name' => 'New',
            'last_name' => 'Patient',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
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
            'lmp' => '2026-06-01',
            'edc' => '2027-03-15',
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
            'iron_taken' => false,
        ]);

        $response2 = $this->actingAs($user)
            ->get(route('consultations.live-requests'));
        $hash2 = $response2->json('queue_version_hash');

        $this->assertNotEquals($hash1, $hash2);
    }
}
