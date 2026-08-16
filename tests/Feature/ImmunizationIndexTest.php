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
            'is_immunization_enrolled' => true,
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

    public function test_due_kpi_counts_infants_in_due_window(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(42));
        $this->infantIn(1, now()->subDays(41));

        $this->get(route('immunizations.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->addWeek()->toDateString(),
        ]))
            ->assertOk()
            ->assertViewHas('dueTodayCount', 2);

        $this->get(route('immunizations.index', [
            'date_from' => now()->addDay()->toDateString(),
            'date_to' => now()->addWeek()->toDateString(),
        ]))
            ->assertOk()
            ->assertViewHas('dueTodayCount', 1);
    }

    public function test_month_filter_shows_patients_due_in_selected_month(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(42));

        $this->get(route('immunizations.index', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertViewHas('dueTodayCount', fn (int $count) => $count >= 1);

        $this->get(route('immunizations.index', ['month' => now()->subMonths(2)->format('Y-m')]))
            ->assertOk()
            ->assertViewHas('dueTodayCount', 0);
    }

    public function test_date_range_overrides_month_filter(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(42));

        $this->get(route('immunizations.index', [
            'month' => now()->subMonths(2)->format('Y-m'),
            'date_from' => now()->toDateString(),
            'date_to' => now()->addWeek()->toDateString(),
        ]))
            ->assertOk()
            ->assertViewHas('dueTodayCount', fn (int $count) => $count >= 1);
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

    public function test_child_overdue_queue_excludes_pneumonia_and_influenza(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(100));

        $this->get(route('immunizations.index'))
            ->assertOk()
            ->assertViewHas('queues', fn (array $queues) => collect($queues['overdue'])
                ->pluck('vaccine')
                ->pluck('vaccine_code')
                ->intersect(['PNEUMONIA', 'FLU'])
                ->isEmpty());
    }

    public function test_pneumonia_and_influenza_are_adult_category(): void
    {
        $this->assertSame('Adult', DB::table('vaccines_lookup')->where('vaccine_code', 'PNEUMONIA')->value('category'));
        $this->assertSame('Adult', DB::table('vaccines_lookup')->where('vaccine_code', 'FLU')->value('category'));
    }

    public function test_adult_mode_excludes_children_from_queues(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(42));

        $this->get(route('immunizations.index', ['mode' => 'adult']))
            ->assertOk()
            ->assertViewHas('mode', 'adult')
            ->assertViewHas('queues', fn (array $queues) => collect($queues)->every(
                fn ($queue) => $queue->isEmpty()
            ));
    }

    public function test_unenrolled_patient_does_not_appear_in_queue(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        Patient::create([
            'household_id' => $household->id,
            'first_name' => 'Adult',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'civil_status' => 'Married',
            'mother_name' => 'Maria',
            'spouse_name' => 'Juan',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => false,
        ]);

        $this->get(route('immunizations.index', ['mode' => 'adult']))
            ->assertOk()
            ->assertViewHas('mode', 'adult')
            ->assertViewHas('queues', fn (array $queues) => collect($queues)->every(
                fn ($queue) => $queue->isEmpty()
            ))
            ->assertViewHas('dueTodayCount', 0)
            ->assertViewHas('overdueCount', 0);
    }

    public function test_enrolled_patient_appears_in_queue(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->infantIn(1, now()->subDays(100));

        $this->get(route('immunizations.index'))
            ->assertOk()
            ->assertViewHas('overdueCount', fn (int $count) => $count >= 1);
    }

    public function test_adult_without_doses_is_excluded_from_overdue(): void
    {
        $this->actingAs($this->userWithPermission());

        $this->zone(1);
        $household = Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz']);

        Patient::create([
            'household_id' => $household->id,
            'first_name' => 'Adult',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'civil_status' => 'Married',
            'mother_name' => 'Maria',
            'spouse_name' => 'Juan',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);

        $this->get(route('immunizations.index', ['mode' => 'adult']))
            ->assertOk()
            ->assertViewHas('mode', 'adult')
            ->assertViewHas('overdueCount', 0);
    }
}
