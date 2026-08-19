<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ImmunizationDueNotificationTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('permissions')->insert([
            ['name' => 'immunizations', 'description' => 'Immunizations module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'household', 'description' => 'Zone-scoped access', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // The migrations seed a full vaccine catalog; replace it with a single
        // deterministic vaccine so queue fixtures are fully controlled.
        DB::table('vaccine_schedules')->delete();
        DB::table('vaccines_lookup')->delete();

        $vaccineId = DB::table('vaccines_lookup')->insertGetId([
            'vaccine_code' => 'TESTVAC',
            'vaccine_name' => 'Test Vaccine',
            'category' => 'Child',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vaccine_schedules')->insert([
            'vaccine_id' => $vaccineId,
            'dose_number' => 1,
            'min_age_days' => 42,
            'gap_days' => null,
            'requires_temp' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_daily_digest_notifies_facility_staff_and_matching_zone_bhws(): void
    {
        $facilityNurse = $this->createUserWithPermissions(['immunizations']);
        $zoneOneBhw = $this->createZoneAssignedUser('Bhw One', 1);
        $zoneTwoBhw = $this->createZoneAssignedUser('Bhw Two', 2);

        $this->makeInfant(now()->subDays(40), 1); // due (earliest date within window)
        $this->makeInfant(now()->subDays(100), 2); // overdue (only relevant to zone 2)

        $this->artisan('notifications:immunization-due')->assertSuccessful();

        $this->assertSame(2, $facilityNurse->notifications()->count());

        $facilityTypes = $facilityNurse->notifications()->get()->pluck('data.type')->sort()->values()->all();
        $this->assertSame(['immunization_due', 'immunization_overdue'], $facilityTypes);

        $this->assertSame(1, $zoneOneBhw->notifications()->count());
        $this->assertSame('immunization_due', $zoneOneBhw->notifications()->first()->data['type']);

        $this->assertSame(1, $zoneTwoBhw->notifications()->count());
        $this->assertSame('immunization_overdue', $zoneTwoBhw->notifications()->first()->data['type']);

        $dueNotification = $facilityNurse->notifications()->get()->firstWhere('data.type', 'immunization_due');
        $this->assertStringContainsString('1 child is due for immunization', $dueNotification->data['message']);
        $this->assertSame(route('immunizations.index'), $dueNotification->data['url']);
    }

    public function test_digest_is_deduplicated_within_the_same_day(): void
    {
        $facilityNurse = $this->createUserWithPermissions(['immunizations']);

        $this->makeInfant(now()->subDays(40), 1);

        $this->artisan('notifications:immunization-due')->assertSuccessful();
        $this->artisan('notifications:immunization-due')->assertSuccessful();

        $this->assertSame(1, $facilityNurse->notifications()->count());
        $this->assertSame('immunization_due', $facilityNurse->notifications()->first()->data['type']);
    }

    public function test_digest_is_pluralized_for_multiple_children(): void
    {
        $facilityNurse = $this->createUserWithPermissions(['immunizations']);

        $this->makeInfant(now()->subDays(40), 1);
        $this->makeInfant(now()->subDays(41), 1);

        $this->artisan('notifications:immunization-due')->assertSuccessful();

        $notification = $facilityNurse->notifications()->first();

        $this->assertSame('immunization_due', $notification->data['type']);
        $this->assertStringContainsString('2 children are due for immunization', $notification->data['message']);
    }

    private function makeInfant(Carbon $dob, int $zoneId): Patient
    {
        DB::table('zones')->updateOrInsert(
            ['id' => $zoneId],
            ['zone_number' => (string) $zoneId, 'created_at' => now(), 'updated_at' => now()]
        );

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
            'residential_address' => 'Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);
    }

    private function createZoneAssignedUser(string $name, int $zoneId): User
    {
        $user = $this->createUserWithPermissions(['immunizations', 'household']);

        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => $name,
            'last_name' => 'Bhw',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->updateOrInsert(
            ['id' => $zoneId],
            [
                'zone_number' => (string) $zoneId,
                'assigned_worker_id' => $workerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $user;
    }
}
