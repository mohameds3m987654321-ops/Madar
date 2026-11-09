<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_login_page_shows_accurate_security_guidance_without_imposing_a_password_length(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('قبل ما تسجّل دخول')
            ->assertSee('ما تشاركش كلمة المرور أو رمز التحقق')
            ->assertSee('فعّل بريدك من رسالة التحقق')
            ->assertSee('قد يوقف الدخول مؤقتًا')
            ->assertDontSee('٦ أحرف');
    }

    public function test_account_is_locked_after_five_failed_logins_and_admin_can_unlock_it(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@example.test',
            'password' => 'correct-password',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->from('/login')->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrors(['email']);
        }

        $user->refresh();
        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertNotNull($user->login_locked_at);

        $this->withoutMiddleware(ThrottleRequests::class);
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])
            ->assertSessionHasErrors(['email'])
            ->assertSessionHas('errors', function ($errors) {
                return str_contains((string) $errors->first('email'), 'مؤقتًا');
            });

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('users.login-unlock', $user))
            ->assertRedirect(route('users.index'));

        $this->assertNull($user->fresh()->login_locked_at);
        $this->assertSame(0, $user->fresh()->failed_login_attempts);
    }

    public function test_login_endpoint_throttles_repeated_attempts_by_ip_and_email(): void
    {
        $user = User::factory()->create(['email' => 'rate-limit@example.test']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_login_endpoint_also_limits_password_spraying_from_one_ip(): void
    {
        for ($account = 1; $account <= 6; $account++) {
            $user = User::factory()->create([
                'email' => "spray-{$account}@example.test",
            ]);

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $this->post(route('login.store'), [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ]);
            }
        }

        $nextUser = User::factory()->create(['email' => 'spray-next@example.test']);
        $this->post(route('login.store'), [
            'email' => $nextUser->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_a_successful_login_clears_failures_before_the_fifth_attempt(): void
    {
        $user = User::factory()->create([
            'email' => 'retry@example.test',
            'password' => 'correct-password',
            'failed_login_attempts' => 4,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])
            ->assertRedirect(route('users.profile'));

        $this->assertSame(0, $user->fresh()->failed_login_attempts);
        $this->assertNull($user->fresh()->login_locked_at);
    }

    public function test_login_lockout_expires_after_the_configured_duration(): void
    {
        $user = User::factory()->create([
            'email' => 'expired-lock@example.test',
            'password' => 'correct-password',
            'failed_login_attempts' => 5,
            'login_locked_at' => now()->subMinutes(16),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('users.profile'));

        $this->assertNull($user->fresh()->login_locked_at);
        $this->assertSame(0, $user->fresh()->failed_login_attempts);
    }

    public function test_successful_login_remembers_the_user_when_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'remember@example.test',
            'password' => 'correct-password',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
            'remember' => 'on',
        ])->assertRedirect(route('users.profile'));

        $this->assertNotNull($user->fresh()->remember_token);
    }
}
