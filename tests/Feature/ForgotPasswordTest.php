<?php

namespace Tests\Feature;

use App\Mail\ForgotPasswordOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_loads(): void
    {
        $this->get(route('password.forgot'))->assertOk();
    }

    public function test_submitting_username_sends_otp_and_redirects_to_verify(): void
    {
        Mail::fake();

        $user = User::factory()->create(['username' => 'bhw_juan', 'email' => 'juan@example.com']);

        $this->post(route('password.forgot.submit'), ['username' => 'bhw_juan'])
            ->assertRedirect(route('password.forgot.verify'));

        Mail::assertSent(ForgotPasswordOtp::class, function (ForgotPasswordOtp $mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $this->assertDatabaseHas('password_resets', [
            'user_id' => $user->id,
            'used' => false,
        ]);
    }

    public function test_unknown_username_still_redirects_generically(): void
    {
        Mail::fake();

        $this->post(route('password.forgot.submit'), ['username' => 'nobody'])
            ->assertRedirect(route('password.forgot.verify'));

        Mail::assertNothingSent();
    }

    public function test_verify_page_requires_a_pending_request(): void
    {
        $this->get(route('password.forgot.verify'))
            ->assertRedirect(route('password.forgot'));
    }

    public function test_valid_otp_resets_the_password(): void
    {
        Mail::fake();

        $user = User::factory()->create(['username' => 'nurse_ana', 'email' => 'ana@example.com']);

        $this->post(route('password.forgot.submit'), ['username' => 'nurse_ana']);

        $otp = Mail::sent(ForgotPasswordOtp::class)->first()->otp;

        $this->post(route('password.forgot.verify.submit'), [
            'username' => 'nurse_ana',
            'otp' => $otp,
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewSecret123!', $user->fresh()->password));
        $this->assertDatabaseHas('password_resets', [
            'user_id' => $user->id,
            'used' => true,
        ]);
    }

    public function test_wrong_otp_does_not_reset_the_password(): void
    {
        Mail::fake();

        $user = User::factory()->create(['username' => 'doc_pedro', 'email' => 'pedro@example.com']);

        $this->post(route('password.forgot.submit'), ['username' => 'doc_pedro']);

        $this->post(route('password.forgot.verify.submit'), [
            'username' => 'doc_pedro',
            'otp' => '000000',
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertSessionHasErrors('otp');

        $this->assertFalse(Hash::check('NewSecret123!', $user->fresh()->password));
        $this->assertDatabaseHas('password_resets', [
            'user_id' => $user->id,
            'used' => false,
        ]);
    }

    public function test_expired_otp_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->create(['username' => 'midwife_liza', 'email' => 'liza@example.com']);

        $this->post(route('password.forgot.submit'), ['username' => 'midwife_liza']);

        $otp = Mail::sent(ForgotPasswordOtp::class)->first()->otp;

        DB::table('password_resets')->where('user_id', $user->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->post(route('password.forgot.verify.submit'), [
            'username' => 'midwife_liza',
            'otp' => $otp,
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertSessionHasErrors('otp');

        $this->assertFalse(Hash::check('NewSecret123!', $user->fresh()->password));
    }
}
