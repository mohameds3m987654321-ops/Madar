<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\EmailVerificationCodeMail;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\TransportException;
use RuntimeException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_displays_accurate_signup_guidance(): void
    {
        $this->get(route('users.create'))
            ->assertOk()
            ->assertSee('بيانات التواصل')
            ->assertSee('بيانات الدراسة')
            ->assertSee('٨ أحرف على الأقل')
            ->assertSee('رمز تأكيد من 6 أرقام على بريدك');
    }

    public function test_seeded_admin_can_access_the_platform_without_email_verification(): void
    {
        config([
            'admin.name' => 'Seeded Admin',
            'admin.email' => 'admin@example.test',
            'admin.password' => 'a-unique-test-password',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('Seeded Admin', $admin->name);
        $this->assertNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('a-unique-test-password', $admin->password));

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'a-unique-test-password',
        ])->assertRedirect(route('users.profile'));

        $this->get(route('users.index'))->assertOk();
    }

    public function test_unverified_admin_is_not_redirected_to_email_verification_after_login(): void
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertRedirect(route('users.profile'));

        $this->get(route('users.index'))->assertOk();
    }

    public function test_seeding_requires_explicit_admin_credentials(): void
    {
        config([
            'admin.name' => null,
            'admin.email' => null,
            'admin.password' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Set ADMIN_NAME, ADMIN_EMAIL, and a strong ADMIN_PASSWORD');

        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeding_does_not_promote_an_existing_non_admin_account(): void
    {
        config([
            'admin.name' => 'Seeded Admin',
            'admin.email' => 'existing@example.test',
            'admin.password' => 'a-unique-test-password',
        ]);
        $user = User::factory()->create([
            'email' => 'existing@example.test',
            'role' => 'student',
        ]);

        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('Seeding should reject an email owned by a non-admin user.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The configured ADMIN_EMAIL already belongs to a non-admin user.',
                $exception->getMessage()
            );
        }

        $this->assertSame('student', $user->fresh()->role);
    }

    public function test_registration_sends_a_verification_email_and_locks_the_account(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();

        $response = $this->post(route('users.store'), [
            'name' => 'New Student',
            'email' => 'student@example.test',
            'student_phone' => '01012345678',
            'parent_phone' => '01112345678',
            'education_stage' => 'secondary',
            'education_grade' => '3',
            'education_system' => 'old',
            'education_type' => 'azhar',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'student@example.test')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('student', $user->role);
        $this->assertSame('01012345678', $user->student_phone);
        $this->assertSame('01112345678', $user->parent_phone);
        $this->assertSame('secondary', $user->education_stage);
        $this->assertSame('3', $user->education_grade);
        $this->assertSame('old', $user->education_system);
        $this->assertSame('azhar', $user->education_type);
        $sentMail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $this->assertNotNull($sentMail);
        $sentMail->assertSeeInHtml('مدار');
        $sentMail->assertSeeInHtml('M4 17.5');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sentMail->code);
        $this->assertTrue(Hash::check($sentMail->code, $user->email_verification_code_hash));
        $this->assertTrue($user->email_verification_code_expires_at->isFuture());

        $this->get(route('users.profile'))
            ->assertRedirect(route('verification.notice'));

        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('أكد بريدك الإلكتروني')
            ->assertSee('student@example.test')
            ->assertSee('رمز التحقق المكوّن من 6 أرقام')
            ->assertSee('storage/logs/laravel.log');
    }

    public function test_registration_sends_verification_email_without_queue_and_queues_admin_alert(): void
    {
        config(['queue.default' => 'database']);
        Mail::fake();
        Queue::fake();
        User::factory()->create(['role' => 'admin']);

        $this->post(route('users.store'), [
            'name' => 'Queued Student',
            'email' => 'queued-student@example.test',
            'student_phone' => '01012345678',
            'parent_phone' => '01112345678',
            'education_stage' => 'secondary',
            'education_grade' => '3',
            'education_system' => 'old',
            'education_type' => 'azhar',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        Mail::assertSent(EmailVerificationCodeMail::class);
        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job) => $job->notification instanceof \App\Notifications\CourseActivityNotification,
        );
        Queue::assertPushed(SendQueuedNotifications::class, 1);
    }

    public function test_registration_requires_student_details_with_supported_education_choices(): void
    {
        $this->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'New Student',
                'email' => 'student@example.test',
                'student_phone' => '',
                'parent_phone' => '',
                'education_stage' => 'university',
                'education_grade' => '4',
                'education_system' => 'unknown',
                'education_type' => 'international',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors([
                'student_phone',
                'parent_phone',
                'education_stage',
                'education_grade',
                'education_type',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'student@example.test']);
    }

    public function test_secondary_registration_requires_a_supported_education_system(): void
    {
        $this->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'New Student',
                'email' => 'student@example.test',
                'student_phone' => '01012345678',
                'parent_phone' => '01112345678',
                'education_stage' => 'secondary',
                'education_grade' => '1',
                'education_type' => 'general',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('education_system');

        $this->assertDatabaseMissing('users', ['email' => 'student@example.test']);
    }

    public function test_preparatory_registration_does_not_require_a_secondary_system(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();

        $this->post(route('users.store'), [
            'name' => 'Preparatory Student',
            'email' => 'preparatory@example.test',
            'student_phone' => '01012345678',
            'parent_phone' => '01112345678',
            'education_stage' => 'preparatory',
            'education_grade' => '2',
            'education_type' => 'general',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'preparatory@example.test',
            'education_stage' => 'preparatory',
            'education_grade' => '2',
            'education_system' => null,
        ]);
    }

    public function test_valid_verification_code_marks_email_verified_and_is_consumed(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        app(\App\Services\EmailVerificationService::class)->sendCode($user);
        $sentMail = Mail::sent(EmailVerificationCodeMail::class)->first();
        $this->assertNotNull($sentMail);
        $code = $sentMail->code;

        $this->actingAs($user)
            ->post(route('verification.code'), ['code' => $code])
            ->assertRedirect(route('users.profile'))
            ->assertSessionHas('success', 'تم تأكيد بريدك الإلكتروني بنجاح.');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_verification_code_hash);

        $this->get(route('users.profile'))->assertOk();
    }

    public function test_wrong_verification_code_does_not_verify_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->post(route('verification.code'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertSame(1, $user->fresh()->email_verification_code_attempts);
    }

    public function test_expired_verification_code_must_be_resent(): void
    {
        $user = User::factory()->unverified()->create([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->post(route('verification.code'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_verification_code_hash);
    }

    public function test_verification_code_is_locked_after_five_incorrect_attempts(): void
    {
        $user = User::factory()->unverified()->create([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('verification.code'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('verification.code'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_old_queued_verification_links_remain_valid(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('users.profile'))
            ->assertSessionHas('success', 'تم تأكيد بريدك الإلكتروني بنجاح.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_can_resend_verification_email(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'وضع البريد الحالي لا يرسل رسائل فعلية؛ رمز التحقق مسجل في storage/logs/laravel.log.');

        Mail::assertSent(EmailVerificationCodeMail::class);
    }

    public function test_verification_email_resend_is_rate_limited(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('verification.send'))->assertRedirect();
        }

        $this->post(route('verification.send'))->assertTooManyRequests();

        Mail::assertSent(EmailVerificationCodeMail::class, 3);
    }

    public function test_email_transport_failure_is_logged_and_account_remains_registered(): void
    {
        $email = 'smtp-failure@example.test';
        Mail::shouldReceive('to')
            ->once()
            ->with($email)
            ->andThrow(new TransportException('SMTP unavailable'));
        Log::spy();

        $this->post(route('users.store'), [
            'name' => 'Mail Failure Student',
            'email' => $email,
            'student_phone' => '01012345678',
            'parent_phone' => '01112345678',
            'education_stage' => 'secondary',
            'education_grade' => '3',
            'education_system' => 'old',
            'education_type' => 'azhar',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('email_error');

        $this->assertDatabaseHas('users', ['email' => $email]);
        $this->assertNotNull(User::where('email', $email)->firstOrFail()->email_verification_code_hash);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_unverified_user_is_sent_to_verification_after_login(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.test',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertRedirect(route('verification.notice'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('courses.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_correct_the_email_address_before_verification(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create([
            'email' => 'mistyped@example.test',
        ]);

        $this->actingAs($user)
            ->patch(route('verification.email.update'), [
                'email' => 'correct@example.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('correct@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        Mail::assertSent(EmailVerificationCodeMail::class);
    }

    public function test_changing_email_after_verification_requires_verification_again(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('users.profile.update'), [
                'name' => $user->name,
                'email' => 'new-address@example.test',
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('new-address@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        Mail::assertSent(EmailVerificationCodeMail::class);

        $this->get(route('users.profile'))
            ->assertRedirect(route('verification.notice'));
    }
}
