<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_send_creates_database_notification_per_recipient_and_busts_header_cache(): void
    {
        $user = $this->createUserWithPermissions();
        Cache::put("header_notifications_{$user->id}", ['stale']);
        Cache::put("header_unread_count_{$user->id}", 9);

        NotificationService::send($user, 'test_type', 'Test title', 'Test message', 'https://example.com');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $user->notifications()->first();

        $this->assertSame('test_type', $notification->data['type']);
        $this->assertSame('Test title', $notification->data['title']);
        $this->assertSame('Test message', $notification->data['message']);
        $this->assertSame('https://example.com', $notification->data['url']);

        $this->assertFalse(Cache::has("header_notifications_{$user->id}"));
        $this->assertFalse(Cache::has("header_unread_count_{$user->id}"));
    }

    public function test_send_accepts_collection_of_recipients(): void
    {
        $users = [
            $this->createUserWithPermissions(),
            $this->createUserWithPermissions(),
        ];

        NotificationService::send($users, 'test_type', 'Title', 'Message');

        foreach ($users as $user) {
            $this->assertSame(1, $user->notifications()->count());
        }
    }

    public function test_send_to_permission_holders_skips_users_without_permission(): void
    {
        $withPermission = $this->createUserWithPermissions(['consultations']);
        $withoutPermission = $this->createUserWithPermissions();

        NotificationService::sendToPermissionHolders('consultations', 'test_type', 'Title', 'Message');

        $this->assertSame(1, $withPermission->notifications()->count());
        $this->assertSame(0, $withoutPermission->notifications()->count());
    }

    public function test_send_to_permission_holders_excludes_the_actor(): void
    {
        $withPermission = $this->createUserWithPermissions(['consultations']);

        NotificationService::sendToPermissionHolders('consultations', 'test_type', 'Title', 'Message', excludeUserId: $withPermission->id);

        $this->assertSame(0, $withPermission->notifications()->count());
    }

    public function test_zone_scoped_user_only_receives_patient_bound_notifications_in_their_zones(): void
    {
        [$zoneOneUser, $zoneOnePatient] = $this->createZoneScopedUser('Zone 1');
        [$zoneTwoUser, $zoneTwoPatient] = $this->createZoneScopedUser('Zone 2');

        NotificationService::sendToPermissionHolders(
            'consultations',
            'test_type',
            'Title',
            'Message',
            patientIds: [$zoneOnePatient]
        );

        $this->assertSame(1, $zoneOneUser->notifications()->count());
        $this->assertSame(0, $zoneTwoUser->notifications()->count());
    }

    public function test_zone_scoped_user_is_skipped_when_no_patient_ids_are_given(): void
    {
        [$zoneUser] = $this->createZoneScopedUser('Zone 1');

        NotificationService::sendToPermissionHolders('consultations', 'test_type', 'Title', 'Message');

        $this->assertSame(0, $zoneUser->notifications()->count());
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createZoneScopedUser(string $zoneNumber): array
    {
        $user = $this->createUserWithPermissions(['consultations', 'household']);

        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Zone',
            'last_name' => 'Worker',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'zone_number' => $zoneNumber,
            'assigned_worker_id' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $zoneId,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('patients')->insertGetId([
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

        return [$user, $patientId];
    }
}
