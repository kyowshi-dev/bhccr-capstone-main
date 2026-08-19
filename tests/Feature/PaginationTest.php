<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function createPatient(int $suffix = 0): Patient
    {
        $zone = Zone::query()->create(['zone_number' => (string) mt_rand(1, 9999)]);

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $zone->id,
            'family_name_head' => 'Dela Cruz',
        ]);

        $unique = $suffix > 0 ? (string) $suffix : '';

        return Patient::query()->create([
            'household_id' => $householdId,
            'last_name' => 'Dela Cruz'.$unique,
            'first_name' => 'Juan',
            'middle_name' => null,
            'suffix' => null,
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'birth_place' => 'Sta. Ana',
            'blood_type' => 'O+',
            'civil_status' => 'Single',
            'educational_attainment' => 'College',
            'employment_status' => 'Employed',
            'mother_name' => 'Maria',
            'spouse_name' => 'Mercedes',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1',
            'is_philhealth_member' => 'n',
            'philhealth_no' => null,
            'membership_category' => null,
            'is_pcb_member' => 'n',
            'has_4ps' => false,
            'has_nhts' => false,
        ]);
    }

    public function test_patients_index_respects_per_page_query(): void
    {
        $admin = $this->createUserWithPermissions(['patients']);
        $this->actingAs($admin);

        foreach (range(1, 25) as $i) {
            $this->createPatient($i);
        }

        $response = $this->get('/patients?per_page=10');

        $response->assertStatus(200);
        $response->assertViewHas('patients', fn ($patients) => $patients->perPage() === 10);
        $response->assertViewHas('patients', fn ($patients) => $patients->total() === 25);
    }

    public function test_invalid_per_page_falls_back_to_default(): void
    {
        $admin = $this->createUserWithPermissions(['patients']);
        $this->actingAs($admin);

        $this->createPatient();

        $response = $this->get('/patients?per_page=7');

        $response->assertStatus(200);
        $response->assertViewHas('patients', fn ($patients) => $patients->perPage() === 20);
    }

    public function test_patients_index_renders_rows_per_page_dropdown(): void
    {
        $admin = $this->createUserWithPermissions(['patients']);
        $this->actingAs($admin);

        $this->createPatient();

        $response = $this->get('/patients');

        $response->assertStatus(200);
        $response->assertSee('Rows per page');
        $response->assertSee('per_page');
    }

    public function test_medicines_index_respects_per_page_query(): void
    {
        $admin = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($admin);

        foreach (range(1, 30) as $i) {
            DB::table('medicines_lookup')->insert([
                'name' => "Medicine {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->get('/medicines?per_page=25');

        $response->assertStatus(200);
        $response->assertViewHas('medicines', fn ($medicines) => $medicines->perPage() === 25);
    }

    public function test_zones_index_renders_rows_per_page_dropdown(): void
    {
        $admin = $this->createUserWithPermissions(['zones']);
        $this->actingAs($admin);

        Zone::query()->create(['zone_number' => '1']);

        $response = $this->get('/zones');

        $response->assertStatus(200);
        $response->assertSee('Rows per page');
    }

    public function test_users_index_renders_rows_per_page_dropdown(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $response = $this->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Rows per page');
        $response->assertViewHas('users', fn ($users) => $users->perPage() === 10);
    }

    public function test_households_index_renders_rows_per_page_dropdown(): void
    {
        $admin = $this->createUserWithPermissions(['household', 'users']);
        $this->actingAs($admin);

        $zone = Zone::query()->create(['zone_number' => '1']);
        DB::table('households')->insertGetId([
            'zone_id' => $zone->id,
            'family_name_head' => 'Dela Cruz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/households');

        $response->assertStatus(200);
        $response->assertSee('Rows per page');
        $response->assertViewHas('households', fn ($households) => $households->perPage() === 500);
    }
}
