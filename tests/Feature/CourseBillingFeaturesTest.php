<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Coupon;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourseBillingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_teacher_can_create_and_deactivate_a_course_coupon(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->post(route('courses.coupons.store', $course), [
                'code' => 'save20',
                'type' => 'percentage',
                'value' => 20,
                'max_redemptions' => 10,
            ])
            ->assertRedirect(route('courses.coupons.index', $course));

        $coupon = Coupon::where('course_id', $course->id)->firstOrFail();
        $this->assertSame('SAVE20', $coupon->code);
        $this->assertTrue($coupon->is_active);

        $this->patch(route('courses.coupons.toggle', [$course, $coupon]))
            ->assertRedirect(route('courses.coupons.index', $course));
        $this->assertFalse($coupon->fresh()->is_active);
    }

    public function test_coupon_discount_is_applied_and_reserved_during_paymob_checkout(): void
    {
        config([
            'services.paymob.secret_key' => 'test-secret-key',
            'services.paymob.public_key' => 'test-public-key',
            'services.paymob.integrations.fawry' => '654',
        ]);
        Http::fakeSequence()->push(['id' => 789, 'client_secret' => 'client-secret']);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $anotherStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $coupon = Coupon::create([
            'course_id' => $course->id,
            'code' => 'SAVE20',
            'type' => 'percentage',
            'value' => 20,
            'max_redemptions' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($student)
            ->post(route('courses.checkout', $course), [
                'payment_method' => 'fawry',
                'phone_number' => '01012345678',
                'coupon_code' => 'save20',
            ])
            ->assertRedirect();

        $enrollment = CourseEnrollment::where('user_id', $student->id)->firstOrFail();
        $this->assertSame(5000, $enrollment->discount_cents);
        $this->assertSame(20000, $enrollment->amount_cents);
        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'course_enrollment_id' => $enrollment->id,
            'status' => 'pending',
        ]);
        Http::assertSent(fn ($request) => $request['amount'] === 20000);

        $this->from(route('courses.show', $course))
            ->actingAs($anotherStudent)
            ->post(route('courses.checkout', $course), [
                'payment_method' => 'fawry',
                'phone_number' => '01012345678',
                'coupon_code' => 'SAVE20',
            ])
            ->assertRedirect(route('courses.show', $course))
            ->assertSessionHasErrors('coupon_code');

        $this->assertDatabaseCount('course_enrollments', 1);
    }

    public function test_students_can_view_invoices_and_request_manual_refunds(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->createCourse($teacher);
        $enrollment = CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'paid',
            'amount_cents' => 25000,
            'paymob_transaction_id' => 'transaction-123',
            'paid_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee($course->title)
            ->assertSee('transaction-123');
        $this->get(route('payments.invoice', $enrollment))
            ->assertOk()
            ->assertSee('فاتورة سداد')
            ->assertSee('250.00');

        $this->post(route('refunds.store', $enrollment), [
            'reason' => 'واجهت مشكلة فنية ولم أتمكن من متابعة محتوى الكورس.',
        ])->assertRedirect(route('payments.index'));

        $refund = RefundRequest::firstOrFail();
        $this->actingAs($admin)
            ->patch(route('refunds.update', $refund), [
                'status' => 'approved',
                'admin_note' => 'تمت الموافقة؛ يرجى تنفيذ التحويل يدويًا.',
            ])
            ->assertRedirect(route('refunds.index'));

        $this->actingAs($admin)
            ->patch(route('refunds.update', $refund), [
                'status' => 'completed',
                'admin_note' => 'تم رد المبلغ عبر بوابة الدفع.',
            ])
            ->assertRedirect(route('refunds.index'));

        $this->assertSame('refunded', $enrollment->fresh()->status);
        $this->assertSame('completed', $refund->fresh()->status);
        $this->actingAs($student)
            ->get(route('payments.invoice', $enrollment))
            ->assertOk()
            ->assertSee('تم الاسترداد');
    }

    public function test_student_cannot_view_another_students_invoice(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $owner = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $enrollment = CourseEnrollment::create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'paid',
            'amount_cents' => 25000,
            'paid_at' => now(),
        ]);

        $this->actingAs($otherStudent)
            ->get(route('payments.invoice', $enrollment))
            ->assertForbidden();
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Billing course',
            'description' => 'Course description',
            'price' => 250,
            'status' => 'published',
        ]);
    }
}
