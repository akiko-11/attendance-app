<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // 会員登録後、認証メールが送信される
    public function test_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'verify@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/attendance');

        $user = User::where('email', 'verify@example.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    // 未認証ユーザーはメール認証誘導画面へ遷移する
    public function test_unverified_user_is_redirected_to_email_verification_page(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertRedirect('/email/verify');

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertStatus(200)
            ->assertViewIs('auth.verify-email');
    }

    // 認証リンクからメール認証を完了でき、勤怠登録画面に遷移する
    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)
            ->get($verificationUrl);

        $response->assertRedirectContains('/attendance');

        $this->assertNotNull(
            $user->fresh()->email_verified_at
        );
    }

    // 未認証ユーザーは認証メールを再送できる
    public function test_unverified_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->from('/email/verify')
            ->post('/email/verification-notification');

        $response->assertRedirect('/email/verify');

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }
}
