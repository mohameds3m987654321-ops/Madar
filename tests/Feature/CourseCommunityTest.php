<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseFaq;
use App\Models\CoursePost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseCommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_teacher_can_publish_and_pin_a_private_media_post_for_paid_students(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enrollStudent($student, $course);

        $this->actingAs($teacher)
            ->post(route('courses.community.posts.store', $course), [
                'body' => 'موعد المراجعة يوم الخميس.',
                'external_url' => 'https://example.test/revision',
                'attachment' => UploadedFile::fake()->create('agenda.pdf', 1, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $post = CoursePost::firstOrFail();
        $this->assertSame($teacher->id, $post->user_id);
        $this->assertSame('https://example.test/revision', $post->external_url);
        $this->assertTrue(Storage::disk('private')->exists($post->attachment_path));
        Storage::disk('public')->assertMissing($post->attachment_path);

        $this->actingAs($teacher)
            ->patch(route('courses.community.posts.pin', [$course, $post]))
            ->assertRedirect();
        $this->assertTrue($post->fresh()->is_pinned);

        $this->actingAs($student)
            ->get(route('courses.community.index', $course))
            ->assertOk()
            ->assertSee('موعد المراجعة يوم الخميس.')
            ->assertSee('مثبت')
            ->assertSee('agenda.pdf')
            ->assertSee('نشاط المجموعة')
            ->assertSee(route('courses.community.posts.attachment', [$course, $post]));

        $this->actingAs($student)
            ->get(route('courses.community.posts.attachment', [$course, $post]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_paid_students_and_teacher_can_comment_and_toggle_likes(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enrollStudent($student, $course);
        $post = $course->posts()->create([
            'user_id' => $teacher->id,
            'body' => 'أهلًا بكم في مجموعة الكورس.',
        ]);

        $this->actingAs($student)
            ->post(route('courses.community.comments.store', [$course, $post]), [
                'body' => 'شكرًا يا دكتور.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->actingAs($teacher)
            ->post(route('courses.community.comments.store', [$course, $post]), [
                'body' => 'أهلًا بك.',
            ])
            ->assertRedirect();
        $this->assertDatabaseCount('course_post_comments', 2);

        $this->actingAs($student)
            ->post(route('courses.community.likes.toggle', [$course, $post]))
            ->assertRedirect();
        $this->assertDatabaseHas('course_post_likes', [
            'course_post_id' => $post->id,
            'user_id' => $student->id,
        ]);

        $this->post(route('courses.community.likes.toggle', [$course, $post]))
            ->assertRedirect();
        $this->assertDatabaseMissing('course_post_likes', [
            'course_post_id' => $post->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_student_question_can_only_be_answered_by_its_author_and_the_course_teacher(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $author = User::factory()->create(['role' => 'student']);
        $anotherStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enrollStudent($author, $course);
        $this->enrollStudent($anotherStudent, $course);
        $post = $course->posts()->create([
            'user_id' => $author->id,
            'body' => 'استفسار خاص بالكورس.',
        ]);

        $this->actingAs($author)
            ->get(route('courses.community.index', $course))
            ->assertOk()
            ->assertSee('أضف توضيحاً لاستفسارك...');

        $this->actingAs($anotherStudent)
            ->get(route('courses.community.index', $course))
            ->assertOk()
            ->assertSee('الرد على هذا الاستفسار متاح لصاحبه ومدرّس الكورس فقط.', false)
            ->assertDontSee('أضف توضيحاً لاستفسارك...');

        $this->actingAs($author)
            ->post(route('courses.community.comments.store', [$course, $post]), [
                'body' => 'توضيح من صاحب السؤال.',
            ])
            ->assertRedirect();

        $this->actingAs($anotherStudent)
            ->post(route('courses.community.comments.store', [$course, $post]), [
                'body' => 'رد طالب آخر.',
            ])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->post(route('courses.community.comments.store', [$course, $post]), [
                'body' => 'إجابة المدرّس.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('course_post_comments', 2);
    }

    public function test_course_teacher_can_manage_faqs_and_paid_students_can_read_them(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enrollStudent($student, $course);

        $this->actingAs($teacher)
            ->post(route('courses.community.faqs.store', $course), [
                'question' => 'متى موعد المحاضرة؟',
                'answer' => 'يوم الأحد الساعة الخامسة.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $faq = CourseFaq::firstOrFail();
        $this->actingAs($student)
            ->get(route('courses.community.index', $course))
            ->assertOk()
            ->assertSee('متى موعد المحاضرة؟')
            ->assertSee('يوم الأحد الساعة الخامسة.')
            ->assertDontSee('إضافة سؤال شائع');

        $this->actingAs($anotherTeacher)
            ->put(route('courses.community.faqs.update', [$course, $faq]), [
                'question' => 'سؤال غير مصرح به',
                'answer' => 'تعديل غير مصرح به',
            ])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->put(route('courses.community.faqs.update', [$course, $faq]), [
                'question' => 'موعد المحاضرة؟',
                'answer' => 'كل يوم أحد الساعة الخامسة.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('course_faqs', [
            'id' => $faq->id,
            'question' => 'موعد المحاضرة؟',
            'answer' => 'كل يوم أحد الساعة الخامسة.',
        ]);

        $this->actingAs($teacher)
            ->delete(route('courses.community.faqs.destroy', [$course, $faq]))
            ->assertRedirect();
        $this->assertDatabaseMissing('course_faqs', ['id' => $faq->id]);
    }

    public function test_group_actions_return_async_fragments_for_live_updates(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enrollStudent($student, $course);

        $postResponse = $this->actingAs($student)
            ->postJson(route('courses.community.posts.store', $course), [
                'body' => 'هل يمكن توضيح موعد الامتحان؟',
            ])
            ->assertCreated()
            ->assertJsonPath('stats.posts', 1)
            ->assertJsonPath('stats.students', 1)
            ->assertJsonPath('message', 'تم نشر المنشور في المجموعة.');
        $this->assertStringContainsString('هل يمكن توضيح موعد الامتحان؟', $postResponse->json('html'));
        $post = CoursePost::firstOrFail();

        $commentResponse = $this->actingAs($teacher)
            ->postJson(route('courses.community.comments.store', [$course, $post]), [
                'body' => 'الامتحان يوم الخميس.',
            ])
            ->assertCreated()
            ->assertJsonPath('comment_count', 1)
            ->assertJsonPath('stats.comments', 1);
        $this->assertStringContainsString('الامتحان يوم الخميس.', $commentResponse->json('html'));

        $this->actingAs($student)
            ->postJson(route('courses.community.likes.toggle', [$course, $post]))
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1)
            ->assertJsonPath('stats.likes', 1);

        $this->actingAs($teacher)
            ->patchJson(route('courses.community.posts.pin', [$course, $post]))
            ->assertOk()
            ->assertJsonPath('pinned', true);

        $this->deleteJson(route('courses.community.posts.destroy', [$course, $post]))
            ->assertOk()
            ->assertJsonPath('stats.posts', 0)
            ->assertJsonPath('stats.comments', 0)
            ->assertJsonPath('stats.likes', 0);
    }

    public function test_only_course_teacher_can_publish_pin_and_moderate_posts(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $this->enrollStudent($student, $course);
        $post = $course->posts()->create([
            'user_id' => $teacher->id,
            'body' => 'إعلان.',
        ]);

        $this->actingAs($anotherTeacher)
            ->get(route('courses.community.index', $course))
            ->assertForbidden();
        $this->actingAs($student)
            ->post(route('courses.community.posts.store', $course), [
                'body' => 'منشور طالب.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('course_posts', ['body' => 'منشور طالب.']);
        $this->actingAs($student)
            ->patch(route('courses.community.posts.pin', [$course, $post]))
            ->assertForbidden();
        $this->actingAs($anotherTeacher)
            ->delete(route('courses.community.posts.destroy', [$course, $post]))
            ->assertForbidden();

        $unenrolled = User::factory()->create(['role' => 'student']);
        $this->actingAs($unenrolled)
            ->get(route('courses.community.index', $course))
            ->assertForbidden();
        $this->get(route('courses.community.posts.attachment', [$course, $post]))
            ->assertForbidden();
    }

    public function test_post_content_and_external_links_are_validated(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->from(route('courses.community.index', $course))
            ->post(route('courses.community.posts.store', $course), [
                'body' => '',
            ])
            ->assertRedirect(route('courses.community.index', $course))
            ->assertSessionHasErrors('body');

        $this->post(route('courses.community.posts.store', $course), [
            'body' => 'رابط غير صالح',
            'external_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('external_url');
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'كورس تجريبي',
            'description' => 'وصف الكورس',
            'price' => 100,
            'status' => 'published',
        ]);
    }

    private function enrollStudent(User $student, Course $course): void
    {
        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'fawry',
            'status' => 'paid',
            'amount_cents' => 10000,
            'paid_at' => now(),
        ]);
    }
}
