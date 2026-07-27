<?php

namespace Tests\Feature;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_stores_request(): void
    {
        $this->seed(); // seed default user roles and admin

        $response = $this->post(route('password.forgot.submit'), [
            'username' => 'admin',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('password_reset_requests', [
            'username_requested' => 'admin',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_view_and_complete_requests(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->first();

        PasswordResetRequest::create([
            'user_id' => $admin->id,
            'username_requested' => 'admin',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('users.password-reset-requests'));

        $response->assertStatus(200);
        $response->assertSee('Password Reset Requests');

        $requestRecord = PasswordResetRequest::first();

        $response = $this->actingAs($admin)->post(route('users.password-reset-requests.complete', ['passwordResetRequest' => $requestRecord->id]), [
            'admin_note' => 'Reset completed by admin',
        ]);

        $response->assertRedirect(route('users.password-reset-requests'));
        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $requestRecord->id,
            'status' => 'completed',
            'admin_note' => 'Reset completed by admin',
        ]);
    }
}
