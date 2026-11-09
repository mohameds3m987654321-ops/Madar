<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_upload_and_delete_private_lesson_attachments(): void
    {
        Storage::fake('private');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $lesson = $course->lessons()->create(['title' => 'Lesson']);

        $this->actingAs($teacher)
            ->post(route('lessons.attachments.store', [$course, $lesson]), [
                'attachments' => [
                    UploadedFile::fake()->create('handout.pdf', 20, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('lessons.edit', [$course, $lesson]))
            ->assertSessionHasNoErrors();

        $attachment = $lesson->attachments()->firstOrFail();
        Storage::disk('private')->assertExists($attachment->path);

        $this->actingAs($teacher)
            ->get(route('lessons.attachments.download', [$lesson, $attachment]))
            ->assertOk()
            ->assertDownload('handout.pdf');

        $this->actingAs($teacher)
            ->delete(route('lessons.attachments.destroy', [$course, $lesson, $attachment]))
            ->assertRedirect(route('lessons.edit', [$course, $lesson]));

        Storage::disk('private')->assertMissing($attachment->path);
        $this->assertDatabaseMissing('lesson_attachments', ['id' => $attachment->id]);
    }

    public function test_only_users_with_course_access_can_download_a_lesson_attachment(): void
    {
        Storage::fake('private');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse($teacher, 'published');
        $lesson = $course->lessons()->create(['title' => 'Lesson']);
        $attachment = $lesson->attachments()->create([
            'path' => 'lessons/'.$lesson->id.'/attachments/handout.pdf',
            'original_name' => 'handout.pdf',
            'mime_type' => 'application/pdf',
            'size' => 20,
        ]);
        Storage::disk('private')->put($attachment->path, '%PDF test');

        $this->actingAs($otherStudent)
            ->get(route('lessons.attachments.download', [$lesson, $attachment]))
            ->assertForbidden();

        CourseEnrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee('handout.pdf')
            ->assertSee(route('lessons.attachments.download', [$lesson, $attachment]), false);

        $this->actingAs($student)
            ->get(route('lessons.attachments.download', [$lesson, $attachment]))
            ->assertOk()
            ->assertDownload('handout.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_teacher_can_only_manage_attachments_for_their_own_course(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($owner);
        $lesson = $course->lessons()->create(['title' => 'Lesson']);

        $this->actingAs($anotherTeacher)
            ->post(route('lessons.attachments.store', [$course, $lesson]), [
                'attachments' => [
                    UploadedFile::fake()->create('handout.pdf', 20, 'application/pdf'),
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('lesson_attachments', 0);
    }

    public function test_deleting_a_lesson_removes_its_private_attachments(): void
    {
        Storage::fake('private');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $lesson = $course->lessons()->create(['title' => 'Lesson']);
        $attachment = $lesson->attachments()->create([
            'path' => 'lessons/'.$lesson->id.'/attachments/handout.pdf',
            'original_name' => 'handout.pdf',
            'mime_type' => 'application/pdf',
            'size' => 20,
        ]);
        Storage::disk('private')->put($attachment->path, '%PDF test');

        $this->actingAs($teacher)
            ->delete(route('lessons.destroy', [$course, $lesson]))
            ->assertRedirect(route('lessons.manage', $course));

        Storage::disk('private')->assertMissing($attachment->path);
        $this->assertDatabaseMissing('lesson_attachments', ['id' => $attachment->id]);
    }

    public function test_teacher_cannot_upload_unsupported_or_excessive_lesson_attachments(): void
    {
        Storage::fake('private');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $lesson = $course->lessons()->create(['title' => 'Lesson']);

        $this->actingAs($teacher)
            ->post(route('lessons.attachments.store', [$course, $lesson]), [
                'attachments' => [
                    UploadedFile::fake()->create('malware.exe', 20, 'application/octet-stream'),
                ],
            ])
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('lesson_attachments', 0);
    }

    private function createCourse(User $teacher, string $status = 'draft'): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Course',
            'description' => 'Course description',
            'price' => 0,
            'status' => $status,
        ]);
    }
}
