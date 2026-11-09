<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LessonManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_edit_order_and_delete_lessons_for_their_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->get(route('lessons.manage', $course))
            ->assertOk()
            ->assertSee('لسه مفيش محاضرات');

        $response = $this->post(route('lessons.store-for-course', $course), [
            'title' => 'First lecture',
            'content' => 'Lecture content',
            'order' => 1,
        ]);

        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();
        $response->assertRedirect(route('lessons.edit', [$course, $lesson]));

        $this->get(route('lessons.edit', [$course, $lesson]))
            ->assertOk()
            ->assertSee('First lecture')
            ->assertSee('المرفقات والملفات')
            ->assertSee('data-upload-progress', false)
            ->assertSee('media-upload-progress.js', false);

        $this->put(route('lessons.update', [$course, $lesson]), [
            'title' => 'Updated lecture',
            'content' => 'Updated content',
            'order' => 3,
        ])->assertRedirect(route('lessons.manage', $course));

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'Updated lecture',
            'order' => 3,
        ]);

        $this->delete(route('lessons.destroy', [$course, $lesson]))
            ->assertRedirect(route('lessons.manage', $course));
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_legacy_create_form_accepts_its_default_zero_order(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->get(route('lessons.create'))
            ->assertOk()
            ->assertSee('مدة الفيديو بالثواني');

        $this->actingAs($teacher)
            ->post(route('lessons.store'), [
                'course_id' => $course->id,
                'title' => 'First lecture',
                'order' => 0,
            ])
            ->assertRedirect(route('lessons.manage', $course))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title' => 'First lecture',
            'order' => 0,
        ]);
    }

    public function test_teacher_cannot_manage_another_teachers_course_or_a_foreign_lesson(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($owner);
        $otherCourse = $this->createCourse($anotherTeacher);
        $lesson = Lesson::create([
            'course_id' => $otherCourse->id,
            'title' => 'Foreign lecture',
        ]);

        $this->actingAs($anotherTeacher)
            ->get(route('lessons.manage', $course))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('lessons.edit', [$course, $lesson]))
            ->assertNotFound();
    }

    public function test_deleting_a_lesson_also_removes_its_student_progress(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lecture',
        ]);
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'progress' => 40,
        ]);

        $this->actingAs($teacher)
            ->delete(route('lessons.destroy', [$course, $lesson]))
            ->assertRedirect(route('lessons.manage', $course));

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_teacher_cannot_upload_a_non_mp4_lesson_video(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->post(route('lessons.store-for-course', $course), [
                'title' => 'Unsupported video',
                'video' => UploadedFile::fake()->create('lecture.avi', 100, 'video/x-msvideo'),
            ])
            ->assertSessionHasErrors('video');

        $this->assertDatabaseMissing('lessons', [
            'course_id' => $course->id,
            'title' => 'Unsupported video',
        ]);
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Owned course',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'draft',
        ]);
    }
}
