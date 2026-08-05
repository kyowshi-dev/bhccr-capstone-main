<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ImmunizationIndexTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'immunizations',
            'description' => 'Immunizations module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function userWithPermission(): User
    {
        return $this->createUserWithPermissions(['immunizations']);
    }

    private function zone(int $id): void
    {
        DB::table('zones')->insertOrIgnore(['id' => $id, 'zone_number' => 'Zone '.$id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function infantIn(int $zoneId, Carbon $dob): Patient
    {
        $this->zone($zoneId);

        return Patient::create([
            'household_id' => Household::create(['zone_id' => $zoneId, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => $dob->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria',
            'spouse_name' => '',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone '.$zoneId.' Sta. Ana',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('immunizations.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->get(route('immunizations.index'))->assertForbidden();
    }

    public function test_index_loads_for_authorized_user(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->get(route('immunizations.index'))
            ->assertOk()
            ->assertViewHas('queues')
            ->assertViewHas('zones');
    }

    public function test_mode_is_persisted_in_session(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->get(route('immunizations.index', ['mode' => 'adult']))
            ->assertOk()
            ->assertSessionHas('immunizations.mode', 'adult');

        $this->get(route('immunizations.index'))
            ->assertOk()
            ->assertViewHas('mode', 'adult');
    }

    public function test_due_today_kpi_counts_infants_matching_date_filter(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(42));
        $this->infantIn(1, now()->subDays(41));

        $this->get(route('immunizations.index'))
            ->assertOk()
            ->assertViewHas('dueTodayCount', 1);

        $this->get(route('immunizations.index', ['date' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertViewHas('dueTodayCount', 1);
    }

    public function test_zone_filter_scopes_due_queue(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(42));
        $this->infantIn(2, now()->subDays(42));

        $this->get(route('immunizations.index', ['zone_id' => 1]))
            ->assertOk()
            ->assertViewHas('dueTodayCount', 1);
    }

    public function test_overdue_kpi_counts_defaulters(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(100));

        $this->get(route('immunizations.index'))
            ->assertOk()
            ->assertViewHas('overdueCount', fn (int $count) => $count >= 1);
    }

    public function test_adult_mode_uses_legacy_records_queue(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(42));

        $this->get(route('immunizations.index', ['mode' => 'adult']))
            ->assertOk()
            ->assertViewHas('mode', 'adult')
            ->assertViewHas('queues', []);
    }
}
