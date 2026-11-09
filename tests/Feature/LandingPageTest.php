<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseReview;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_published_content_and_only_real_reviews(): void
    {
        $teacher = User::factory()->create([
            'name' => 'مدرس مدار',
            'role' => 'teacher',
        ]);
        $student = User::factory()->create([
            'name' => 'طالب مدار',
            'role' => 'student',
        ]);
        $course = $this->createCourse($teacher, 'دورة منشورة');
        $draft = $this->createCourse($teacher, 'دورة غير منشورة', 'draft');

        Lesson::create([
            'course_id' => $course->id,
            'title' => 'الدرس الأول',
            'content' => 'محتوى الدرس',
            'order' => 1,
        ]);
        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'rating' => 3,
            'review' => 'تقييم حقيقي للدورة.',
        ]);
        CourseReview::create([
            'course_id' => $draft->id,
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'rating' => 5,
            'review' => 'تقييم دورة غير منشورة.',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('دورة منشورة')
            ->assertDontSee('دورة غير منشورة')
            ->assertSee('مدرس مدار')
            ->assertSee('تقييم حقيقي للدورة.')
            ->assertDontSee('تقييم دورة غير منشورة.')
            ->assertSee('3.0 / 5')
            ->assertSee('1 درس');
    }

    public function test_homepage_cache_is_invalidated_when_public_course_content_changes(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'دورة مخزنة مؤقتًا');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('تقييم جديد');

        $this->assertTrue(Cache::has(Course::HOME_PAGE_CACHE_KEY));

        Lesson::create([
            'course_id' => $course->id,
            'title' => 'الدرس الأول',
            'order' => 1,
        ]);
        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'rating' => 5,
            'review' => 'تقييم جديد',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('تقييم جديد')
            ->assertSee('1 درس');
    }

    public function test_homepage_cache_is_invalidated_when_a_paid_enrollment_is_created(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'الدورة العامة');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('+1');

        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('+1');
    }

    public function test_registration_page_is_public_and_the_homepage_uses_it(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('users.create'));

        $this->get('/Sigin')
            ->assertRedirect('/');

        $this->get(route('users.create'))
            ->assertOk()
            ->assertSee('إنشاء حساب')
            ->assertSee('action="'.route('users.store').'"', false);
    }

    public function test_homepage_uses_the_provided_hero_video(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('autoplay muted loop playsinline', false)
            ->assertSee('src="'.asset('videos/madar-intro.mp4').'"', false);
    }

    public function test_homepage_reveals_content_smoothly_and_respects_reduced_motion(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('prefers-reduced-motion: reduce', false)
            ->assertSee('IntersectionObserver', false)
            ->assertSee('md-reveal', false)
            ->assertSee('rootMargin: \'0px 0px -40px 0px\'', false);
    }

    public function test_signed_in_students_are_sent_to_the_course_catalog_from_the_homepage(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get('/')
            ->assertOk()
            ->assertSee('ابدأ التعلّم')
            ->assertSee(route('courses.index'));
    }

    public function test_signed_in_user_can_save_and_retrieve_a_theme_preference(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->postJson(route('appearance.update'), ['theme' => 'dark'])
            ->assertOk()
            ->assertJson(['theme' => 'dark']);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'preferred_theme' => 'dark',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-theme="dark"', false);

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('data-theme="dark"', false)
            ->assertSee(asset('css/appearance.css'), false);

        $this->get(route('users.profile'))
            ->assertOk()
            ->assertSee('data-theme="dark"', false)
            ->assertSee('id="theme-toggle"', false);
    }

    public function test_theme_preference_rejects_invalid_values_and_guests_cannot_save_it(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->postJson(route('appearance.update'), ['theme' => 'system'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('theme');

        auth()->logout();

        $this->postJson(route('appearance.update'), ['theme' => 'dark'])
            ->assertUnauthorized();
    }

    private function createCourse(
        User $teacher,
        string $title,
        string $status = 'published',
    ): Course {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => $title,
            'description' => 'وصف مختصر للدورة.',
            'price' => 0,
            'status' => $status,
        ]);
    }
}
