<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_form_is_accessible_for_guests(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('نسيت كلمة المرور');
    }

    public function test_reset_link_email_is_sent_to_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset-me@example.test']);

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_link_reveals_nothing_for_unknown_email(): void
    {
        Notification::fake();

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ghost@example.test']);

        $response->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user) {
                // استخراج التوكن الأصلي من جدول password_reset_tokens غير ممكن (مخزّن مجزّأً)،
                // لذا ننشئ سجلًا معروفًا لاختبار المسار الكامل.
                $plainToken = 'plain-test-token';
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                DB::table('password_reset_tokens')->insert([
                    'email' => $user->email,
                    'token' => hash('sha256', $plainToken),
                    'created_at' => now(),
                ]);

                $this->get(route('password.reset', ['token' => $plainToken, 'email' => $user->email]))
                    ->assertOk()
                    ->assertSee('تعيين كلمة مرور جديدة');

                $this->post(route('password.update'), [
                    'token' => $plainToken,
                    'email' => $user->email,
                    'password' => 'new-password-123',
                    'password_confirmation' => 'new-password-123',
                ])->assertRedirect(route('login'))
                    ->assertSessionHas('success');

                $this->assertTrue(app('hash')->check('new-password-123', $user->fresh()->password));

                // التوكن يُحذف بعد الاستخدام (مرة واحدة فقط)
                $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

                return true;
            }
        );
    }

    public function test_reset_rejects_expired_token(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => hash('sha256', 'expired-token'),
            'created_at' => now()->subHours(3),
        ]);

        $this->from(route('password.reset', ['token' => 'expired-token', 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => 'expired-token',
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('token');

        $this->assertFalse(app('hash')->check('new-password-123', $user->fresh()->password));
    }

    public function test_reset_rejects_wrong_token(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => hash('sha256', 'the-real-token'),
            'created_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'token' => 'not-the-real-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('token');

        $this->assertFalse(app('hash')->check('new-password-123', $user->fresh()->password));
    }

    public function test_reset_requires_strong_password_and_confirmation(): void
    {
        $user = User::factory()->create();

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => hash('sha256', 'valid-token'),
            'created_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'token' => 'valid-token',
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => 'valid-token',
            'email' => $user->email,
            'password' => 'long-enough-password',
            'password_confirmation' => 'different-confirmation',
        ])
            ->assertSessionHasErrors('password');
    }
}
