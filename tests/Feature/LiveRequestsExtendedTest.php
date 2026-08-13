<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_live_requests_returns_standard_response(): void
    {
        $user = $this->createUserWithPermissions(['consultations']);

        $response = $this->actingAs($user)
            ->get(route('consultations.live-requests'))
            ->assertOk()
            ->assertJsonStructure(['hasRequest']);

        $response->assertJsonMissing(['queue_counts']);
        $response->assertJsonMissing(['queue_version_hash']);
    }

    public function test_live_requests_returns_standard_response_for_midwife(): void
    {
        $user = $this->createUserWithPermissions([
            'consultations',
            'maternal',
        ]);

        $this->actingAs($user)
            ->get(route('consultations.live-requests'))
            ->assertOk()
            ->assertJsonStructure(['hasRequest'])
            ->assertJsonMissing(['queue_counts'])
            ->assertJsonMissing(['queue_version_hash']);
    }

    public function test_live_requests_unauthenticated(): void
    {
        $this->get(route('consultations.live-requests'))
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Unauthenticated.']);
    }
}
