<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourseCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_details_offer_vodafone_cash_and_fawry_payment(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();

        $this->actingAs($student)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('فودافون كاش')
            ->assertSee('فوري')
            ->assertSee(route('courses.checkout', $course))
            ->assertSee('كورس جديد')
            ->assertSeeInOrder([
                'aria-labelledby="course-title"',
                'aria-labelledby="about-title"',
                'aria-labelledby="lessons-title"',
                'aria-labelledby="reviews-title"',
            ], false);
    }

    public function test_students_cannot_view_or_enroll_in_draft_courses(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $course->update(['price' => 0, 'status' => 'draft']);

        $this->actingAs($student)
            ->get(route('courses.show', $course))
            ->assertNotFound();

        $this->post(route('courses.enroll', $course))
            ->assertNotFound();

        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_course_owner_can_preview_their_draft_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $course->update(['status' => 'draft']);

        $this->actingAs($teacher)
            ->get(route('courses.show', $course))
            ->assertOk();
    }

    public function test_students_cannot_start_checkout_for_draft_courses(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();
        $course->update(['status' => 'draft']);

        $this->actingAs($student)
            ->post(route('courses.checkout', $course), [
                'payment_method' => 'fawry',
                'phone_number' => '01012345678',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_checkout_creates_paymob_payment_for_selected_method(): void
    {
        config([
            'services.paymob.secret_key' => 'test-secret-key',
            'services.paymob.public_key' => 'test-public-key',
            'services.paymob.integrations.vodafone_cash' => '456',
        ]);

        Http::fakeSequence()
            ->push(['id' => 789, 'client_secret' => 'client-secret']);

        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();

        $this->actingAs($student)
            ->post(route('courses.checkout', $course), [
                'payment_method' => 'vodafone_cash',
                'phone_number' => '01012345678',
            ])
            ->assertRedirect('https://accept.paymob.com/unifiedcheckout/?publicKey=test-public-key&clientSecret=client-secret');

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'vodafone_cash',
            'status' => 'pending',
            'amount_cents' => 25000,
            'paymob_order_id' => '789',
        ]);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/intention/')
            && $request['payment_methods'] === [456]
            && $request['amount'] === 25000
        );
    }

    public function test_student_with_a_paid_enrollment_cannot_create_another_checkout(): void
    {
        Http::fake();

        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'paid',
            'amount_cents' => 25000,
            'paid_at' => now(),
        ]);

        $this->actingAs($student)
            ->post(route('courses.checkout', $course), [
                'payment_method' => 'fawry',
                'phone_number' => '01012345678',
            ])
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('success');

        Http::assertNothingSent();
    }

    public function test_coupon_validation_errors_are_generic_and_checkout_attempts_are_limited(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();
        $checkout = [
            'payment_method' => 'fawry',
            'phone_number' => '01012345678',
            'coupon_code' => 'UNKNOWN',
        ];

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->actingAs($student)
                ->from(route('courses.show', $course))
                ->post(route('courses.checkout', $course), $checkout)
                ->assertSessionHasErrors([
                    'coupon_code' => 'كود الخصم غير صالح أو غير متاح حاليًا.',
                ]);
        }

        $this->actingAs($student)
            ->post(route('courses.checkout', $course), $checkout)
            ->assertTooManyRequests();

        $this->assertDatabaseMissing('course_enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_signed_successful_paymob_callback_enrolls_the_student(): void
    {
        config([
            'services.paymob.hmac_secret' => 'test-hmac-secret',
            'services.paymob.integrations.fawry' => '654',
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();
        $enrollment = CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'pending',
            'amount_cents' => 25000,
            'paymob_order_id' => '789',
        ]);

        $payload = $this->signedPaymobCallbackPayload();
        $this->get(route('payments.paymob.callback', $payload))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('success');

        $this->get(route('payments.paymob.callback', $payload))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('course_enrollments', [
            'id' => $enrollment->id,
            'status' => 'paid',
            'paymob_transaction_id' => '987',
        ]);
        $this->assertDatabaseHas('paymob_callback_logs', [
            'order_id' => '789',
            'transaction_id' => '987',
            'signature_valid' => true,
            'outcome' => 'duplicate_success',
        ]);
    }

    public function test_signed_successful_paymob_callback_cannot_reverse_a_refund(): void
    {
        config([
            'services.paymob.hmac_secret' => 'test-hmac-secret',
            'services.paymob.integrations.fawry' => '654',
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();
        $enrollment = CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'refunded',
            'amount_cents' => 25000,
            'paymob_order_id' => '789',
        ]);

        $this->get(route('payments.paymob.callback', $this->signedPaymobCallbackPayload()))
            ->assertStatus(409);

        $this->assertDatabaseHas('course_enrollments', [
            'id' => $enrollment->id,
            'status' => 'refunded',
            'paymob_transaction_id' => null,
            'paid_at' => null,
        ]);
    }

    public function test_signed_paymob_callback_with_the_wrong_amount_does_not_enroll_the_student(): void
    {
        config([
            'services.paymob.hmac_secret' => 'test-hmac-secret',
            'services.paymob.integrations.fawry' => '654',
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();
        $enrollment = CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'pending',
            'amount_cents' => 25000,
            'paymob_order_id' => '789',
        ]);

        $payload = $this->signedPaymobCallbackPayload(['amount_cents' => '24999']);

        $this->get(route('payments.paymob.callback', $payload))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('course_enrollments', [
            'id' => $enrollment->id,
            'status' => 'failed',
            'paymob_transaction_id' => '987',
        ]);
        $this->assertDatabaseHas('paymob_callback_logs', [
            'order_id' => '789',
            'signature_valid' => true,
            'outcome' => 'payment_failed',
            'failure_reason' => 'payment_not_confirmed_or_data_mismatch',
        ]);
    }

    public function test_unenrolled_student_cannot_open_a_course_lesson(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson',
        ]);

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson))
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHas('access_notice', 'المحاضرة دي متاحة للمشتركين فقط. اشترك في الكورس علشان تقدر تشوفها.');

        $this->followingRedirects()
            ->actingAs($student)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee('id="access-toast"', false)
            ->assertSee('المحاضرة دي متاحة للمشتركين فقط.');
    }

    public function test_callback_with_an_invalid_signature_does_not_enroll_the_student(): void
    {
        config(['services.paymob.hmac_secret' => 'test-hmac-secret']);

        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();
        $enrollment = CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'pending',
            'amount_cents' => 25000,
            'paymob_order_id' => '789',
        ]);

        $this->get(route('payments.paymob.callback', [
            'order' => ['id' => '789'],
            'hmac' => 'invalid-signature',
        ]))->assertForbidden();

        $this->assertDatabaseHas('course_enrollments', [
            'id' => $enrollment->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('paymob_callback_logs', [
            'order_id' => '789',
            'signature_valid' => false,
            'outcome' => 'rejected',
            'failure_reason' => 'invalid_signature',
        ]);
    }

    private function createCourse(?User $teacher = null): Course
    {
        $teacher ??= User::factory()->create(['role' => 'teacher']);

        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 250,
            'status' => 'published',
        ]);
    }

    private function signedPaymobCallbackPayload(array $overrides = []): array
    {
        $payload = array_replace_recursive([
            'amount_cents' => '25000',
            'created_at' => '2026-10-12T15:00:00.000000Z',
            'currency' => 'EGP',
            'error_occured' => 'false',
            'has_parent_transaction' => 'false',
            'id' => '987',
            'integration_id' => '654',
            'is_3d_secure' => 'false',
            'is_auth' => 'true',
            'is_capture' => 'false',
            'is_refunded' => 'false',
            'is_standalone_payment' => 'true',
            'is_voided' => 'false',
            'order' => ['id' => '789'],
            'owner' => '1',
            'pending' => 'false',
            'source_data' => [
                'pan' => 'NA',
                'sub_type' => 'Fawry',
                'type' => 'cash',
            ],
            'success' => 'true',
        ], $overrides);

        $signedValues = [
            $payload['amount_cents'],
            $payload['created_at'],
            $payload['currency'],
            $payload['error_occured'],
            $payload['has_parent_transaction'],
            $payload['id'],
            $payload['integration_id'],
            $payload['is_3d_secure'],
            $payload['is_auth'],
            $payload['is_capture'],
            $payload['is_refunded'],
            $payload['is_standalone_payment'],
            $payload['is_voided'],
            $payload['order']['id'],
            $payload['owner'],
            $payload['pending'],
            $payload['source_data']['pan'],
            $payload['source_data']['sub_type'],
            $payload['source_data']['type'],
            $payload['success'],
        ];
        $payload['hmac'] = hash_hmac(
            'sha512',
            implode('', $signedValues),
            'test-hmac-secret'
        );

        return $payload;
    }
}
