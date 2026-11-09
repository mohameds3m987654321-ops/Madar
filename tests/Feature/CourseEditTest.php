<?php

namespace Tests\Feature;

use App\Jobs\FinalizeMediaUpload;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_their_course_edit_form_with_current_values(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->get(route('courses.edit', $course))
            ->assertOk()
            ->assertSee('تعديل الكورس')
            ->assertSee('Original course', false)
            ->assertSee('Original description', false)
            ->assertSee('name="title"', false)
            ->assertSee('data-upload-progress', false)
            ->assertSee('media-upload-progress.js', false)
            ->assertDontSee('كورس #UX-204');
    }

    public function test_course_create_form_includes_upload_progress_ui(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('courses.create'))
            ->assertOk()
            ->assertSee('data-upload-progress', false)
            ->assertSee('media-upload-progress.css', false)
            ->assertSee('media-upload-progress.js', false);
    }

    public function test_teacher_can_update_their_course_details(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->put(route('courses.update', $course), [
                'title' => 'Updated course',
                'description' => 'Updated description',
                'price' => '125.50',
            ])
            ->assertRedirect(route('courses.edit', $course))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Updated course',
            'description' => 'Updated description',
            'price' => '125.50',
        ]);
    }

    public function test_teacher_can_upload_a_raster_course_image(): void
    {
        Storage::fake('public');
        Queue::fake();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->put(route('courses.update', $course), [
                'title' => 'Original course',
                'description' => 'Original description',
                'price' => 250,
                'image' => UploadedFile::fake()->createWithContent(
                    'course.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
                ),
            ])
            ->assertRedirect(route('courses.edit', $course))
            ->assertSessionHasNoErrors();

        Queue::assertPushed(FinalizeMediaUpload::class);
    }

    public function test_teacher_cannot_upload_an_svg_course_image(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $this->actingAs($teacher)
            ->put(route('courses.update', $course), [
                'title' => 'Original course',
                'description' => 'Original description',
                'price' => 250,
                'image' => UploadedFile::fake()->createWithContent(
                    'course.svg',
                    '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
                ),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'image' => null,
        ]);
    }

    public function test_course_price_must_fit_the_database_precision_and_use_at_most_two_decimals(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $payload = [
            'title' => 'Original course',
            'description' => 'Original description',
        ];

        $this->actingAs($teacher)
            ->put(route('courses.update', $course), [...$payload, 'price' => '100000000'])
            ->assertSessionHasErrors('price');

        $this->put(route('courses.update', $course), [...$payload, 'price' => '12.345'])
            ->assertSessionHasErrors('price');
    }

    public function test_teacher_cannot_edit_another_teachers_course(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($owner);

        $this->actingAs($anotherTeacher)
            ->get(route('courses.edit', $course))
            ->assertForbidden();

        $this->actingAs($anotherTeacher)
            ->put(route('courses.update', $course), [
                'title' => 'Unauthorized update',
                'description' => 'This update should not be saved.',
                'price' => 100,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Original course',
        ]);
    }

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Original course',
            'description' => 'Original description',
            'price' => 250,
            'status' => 'draft',
        ]);
    }
}
