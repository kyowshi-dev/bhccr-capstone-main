<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use AssignsRolesAndPermissions, DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure all migrations are run
        $this->artisan('migrate');
    }

    public function test_session_timeout_setting_can_be_updated(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $response = $this->actingAs($user)->put(route('profile.settings.update'), [
            'session_timeout' => 30,
            'login_max_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'password_min_length' => 8,
            'password_require_uppercase' => true,
            'password_require_number' => true,
            'password_require_symbol' => false,
        ]);

        $response->assertRedirect(route('profile.settings'));
        $response->assertSessionHas('success', 'Security settings updated successfully.');

        $this->assertEquals('30', ApplicationSetting::get('session_timeout'));
    }

    public function test_session_status_reports_active_for_authenticated_session(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $sessionId = session()->getId();

        // Reset the guard so it is resolved again from the session store.
        Auth::forgetGuards();

        $response = $this->withCookie(config('session.cookie'), $sessionId)
            ->getJson(route('session.status'));

        $response->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_session_status_reports_inactive_for_anonymous_session(): void
    {
        $this->getJson(route('session.status'))
            ->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonPath('user', null);
    }

    public function test_session_expires_after_timeout(): void
    {
        // Order matters: set the lifetime BEFORE ApplicationSetting::set(),
        // because setting a value triggers LogsActivity -> Auth::id(), which
        // resolves the session store (and freezes the DB handler's lifetime).
        config(['session.lifetime' => 1]);
        ApplicationSetting::set('session_timeout', 1);

        $user = User::factory()->create(['is_active' => true]);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $sessionId = session()->getId();

        // Simulate a session that has been idle longer than the configured lifetime.
        DB::table('sessions')->where('id', $sessionId)->update([
            'last_activity' => now()->subMinutes(5)->timestamp,
        ]);

        // Drop cached auth/session state so it is rebuilt from the store (which
        // reads last_activity from the sessions table on every request).
        Auth::forgetGuards();
        session()->flush();

        // The public session-status endpoint reports the expiry as JSON - never
        // as an HTML redirect - so the frontend can render the modal deterministically.
        $this->getJson(route('session.status'))
            ->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonPath('lifetime_minutes', 1);

        // Protected pages redirect to the login page once the session is expired.
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_session_heartbeat_reports_ok(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->getJson(route('session.heartbeat'))
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_session_status_lifetime_is_clamped_to_at_least_one_minute(): void
    {
        ApplicationSetting::set('session_timeout', 0);

        $this->refreshApplication();
        $this->artisan('migrate');

        $this->assertGreaterThanOrEqual(1, (int) config('session.lifetime'));
    }
}
