<?php

namespace Tests\Feature;

use App\Mail\ForgotPasswordOtp;
use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class SecuritySettingsTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insertOrIgnore([
            ['id' => 1, 'role_name' => 'Admin'],
            ['id' => 2, 'role_name' => 'Nurse'],
            ['id' => 3, 'role_name' => 'BHW'],
        ]);

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'users', 'description' => 'Access to User Management', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    // ============================================================
    // ACCOUNT LOCKOUT
    // ============================================================

    public function test_account_locks_after_max_failed_attempts(): void
    {
        ApplicationSetting::set('login_max_attempts', 3);
        ApplicationSetting::set('lockout_duration_minutes', 15);

        $user = User::factory()->create(['is_active' => true]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'username' => $user->username,
                'password' => 'WrongPassword123!',
            ]);
        }

        $user->refresh();

        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
        $this->assertTrue($user->isLocked());
    }

    public function test_locked_account_cannot_login_even_with_correct_password(): void
    {
        ApplicationSetting::set('login_max_attempts', 2);
        ApplicationSetting::set('lockout_duration_minutes', 15);

        $user = User::factory()->create(['is_active' => true]);

        $this->post('/login', ['username' => $user->username, 'password' => 'WrongPassword123!']);
        $this->post('/login', ['username' => $user->username, 'password' => 'WrongPassword123!']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString('locked', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        ApplicationSetting::set('login_max_attempts', 5);

        $user = User::factory()->create(['is_active' => true]);

        $this->post('/login', ['username' => $user->username, 'password' => 'WrongPassword123!']);
        $this->post('/login', ['username' => $user->username, 'password' => 'WrongPassword123!']);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_lock_expiry_allows_login_again(): void
    {
        ApplicationSetting::set('login_max_attempts', 2);

        $user = User::factory()->create([
            'is_active' => true,
            'locked_until' => now()->subMinute(),
            'failed_login_attempts' => 0,
        ]);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    // ============================================================
    // PASSWORD POLICY
    // ============================================================

    public function test_weak_password_rejected_when_changing_password(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        $this->actingAs($user);

        $this->post('/settings/account', [
            'current_password' => 'Password123!',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Password123!', $user->fresh()->password));
    }

    public function test_strong_password_accepted_when_changing_password(): void
    {
        $user = User::factory()->create(['password' => 'Password123!']);
        $this->actingAs($user);

        $this->post('/settings/account', [
            'current_password' => 'Password123!',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertRedirect(route('settings.account'));

        $this->assertTrue(Hash::check('StrongPass123', $user->fresh()->password));
    }

    public function test_weak_password_rejected_when_creating_user(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $this->post('/users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
            'role_id' => 2,
        ])->assertSessionHasErrors('password');

        $this->assertNull(User::where('username', 'testuser')->first());
    }

    public function test_symbol_requirement_is_configurable(): void
    {
        ApplicationSetting::set('password_require_symbol', '1');

        $user = User::factory()->create(['password' => 'Password123!']);
        $this->actingAs($user);

        $this->post('/settings/account', [
            'current_password' => 'Password123!',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertSessionHasErrors('password');

        $this->post('/settings/account', [
            'current_password' => 'Password123!',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('settings.account'));
    }

    public function test_password_policy_applies_to_password_reset_flow(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_active' => true]);

        $this->post('/password/forgot', ['username' => $user->username]);

        $mail = Mail::sent(ForgotPasswordOtp::class)->first();
        $this->assertNotNull($mail);

        $this->post('/password/forgot/verify', [
            'username' => $user->username,
            'otp' => $mail->otp,
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    // ============================================================
    // SECURITY HEADERS
    // ============================================================

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_hsts_header_only_sent_over_https(): void
    {
        $this->get('/login')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get('/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    // ============================================================
    // RE-AUTHENTICATION FOR ROLE CHANGES
    // ============================================================

    public function test_role_change_requires_current_password(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $target = $this->createUserWithPermissions([]);
        $originalRole = $target->role_id;
        $this->actingAs($admin);

        $this->put("/users/{$target->id}", [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'username' => $target->username,
            'email' => $target->email,
            'role_id' => 3,
        ])->assertSessionHasErrors('current_password');

        $this->assertSame($originalRole, $target->fresh()->role_id);
    }

    public function test_role_change_with_correct_password_succeeds(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $target = $this->createUserWithPermissions([]);
        $this->actingAs($admin);

        $this->put("/users/{$target->id}", [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'username' => $target->username,
            'email' => $target->email,
            'role_id' => 3,
            'current_password' => 'password',
        ])->assertRedirect(route('users.index'));

        $this->assertSame(3, $target->fresh()->role_id);
    }

    public function test_update_without_role_change_does_not_require_password(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $target = $this->createUserWithPermissions([]);
        DB::table('health_workers')->insert([
            'user_id' => $target->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
            'role' => 'Staff',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($admin);

        $this->put("/users/{$target->id}", [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'username' => $target->username,
            'email' => $target->email,
            'role_id' => $target->role_id,
        ])->assertRedirect(route('users.index'));

        $healthWorker = DB::table('health_workers')->where('user_id', $target->id)->first();
        $this->assertSame('Updated', $healthWorker->first_name);
    }
}
