<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class BreadcrumbTrailTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private User $user;

    private int $patientId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUserWithPermissions(['patients', 'consultations']);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'employment_status' => 'Employed',
            'mother_name' => 'Senior',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_trail_reflects_actual_navigation_path(): void
    {
        $this->actingAs($this->user);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('patients.index'))->assertOk();
        $response = $this->get(route('patients.show', $this->patientId));

        $response->assertOk();
        $this->assertSame([
            ['name' => 'Dashboard', 'url' => route('dashboard')],
            ['name' => 'Patients', 'url' => route('patients.index')],
            ['name' => 'Patient Details', 'url' => route('patients.show', $this->patientId)],
        ], session('breadcrumbs.trail'));
    }

    public function test_revisiting_an_earlier_page_truncates_the_trail(): void
    {
        $this->actingAs($this->user);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('patients.index'))->assertOk();
        $this->get(route('patients.show', $this->patientId))->assertOk();
        $this->get(route('patients.index'))->assertOk();

        $this->assertSame([
            ['name' => 'Dashboard', 'url' => route('dashboard')],
            ['name' => 'Patients', 'url' => route('patients.index')],
        ], session('breadcrumbs.trail'));
    }

    public function test_deep_link_seeds_the_trail_from_the_static_hierarchy(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('patients.show', $this->patientId));

        $response->assertOk();
        $this->assertSame([
            ['name' => 'Dashboard', 'url' => route('dashboard')],
            ['name' => 'Patients', 'url' => route('patients.index')],
            ['name' => 'Patient Details', 'url' => route('patients.show', $this->patientId)],
        ], session('breadcrumbs.trail'));
    }

    public function test_navigating_to_dashboard_resets_the_trail(): void
    {
        $this->actingAs($this->user);

        $this->get(route('patients.index'))->assertOk();
        $this->get(route('patients.show', $this->patientId))->assertOk();
        $this->get(route('dashboard'))->assertOk();

        $this->assertSame([
            ['name' => 'Dashboard', 'url' => route('dashboard')],
        ], session('breadcrumbs.trail'));
    }

    public function test_ajax_endpoints_do_not_pollute_the_trail(): void
    {
        $this->actingAs($this->user);

        $this->get(route('patients.index'))->assertOk();
        $this->getJson(route('search.patients', ['query' => 'Jane']));

        $this->assertSame([
            ['name' => 'Dashboard', 'url' => route('dashboard')],
            ['name' => 'Patients', 'url' => route('patients.index')],
        ], session('breadcrumbs.trail'));
    }

    public function test_non_page_get_routes_do_not_pollute_the_trail(): void
    {
        $this->actingAs($this->user);

        $this->get(route('patients.index'))->assertOk();
        $this->get(route('session.heartbeat'));

        $this->assertSame([
            ['name' => 'Dashboard', 'url' => route('dashboard')],
            ['name' => 'Patients', 'url' => route('patients.index')],
        ], session('breadcrumbs.trail'));
    }

    public function test_guests_never_record_a_trail(): void
    {
        $this->get(route('login'))->assertOk();

        $this->assertNull(session('breadcrumbs.trail'));
    }
}
