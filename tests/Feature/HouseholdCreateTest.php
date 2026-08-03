<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HouseholdCreateTest extends TestCase
{
    use RefreshDatabase;

    private function authorizedUser(): User
    {
        $user = User::factory()->create();

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'household',
            'description' => 'Access to Households module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users_permissions')->insert([
            'user_id' => $user->id,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        DB::table('zones')->insert(['id' => 2, 'zone_number' => '2']);

        return $user;
    }

    public function test_create_page_loads_with_zones_for_authorized_user(): void
    {
        $this->actingAs($this->authorizedUser());

        $response = $this->get(route('households.create'));
        $response->assertOk();
        $response->assertSee('Zone 1');
        $response->assertSee('Zone 2');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('households.create'))->assertRedirect(route('login'));
    }

    public function test_store_creates_household(): void
    {
        $this->actingAs($this->authorizedUser());

        $this->post(route('households.store'), [
            'zone_id' => 1,
            'family_name_head' => 'Dela Cruz',
            'contact_number' => '+63-9171234567',
        ])->assertRedirect(route('households.index'));

        $this->assertDatabaseHas('households', [
            'zone_id' => 1,
            'family_name_head' => 'Dela Cruz',
            'contact_number' => '+63-9171234567',
        ]);
    }
}
