<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseCatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_catalog_uses_database_queries_and_reflects_course_changes(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Cached title',
            'description' => 'Course description',
            'price' => 0,
            'status' => 'published',
        ]);

        $this->actingAs($student)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Cached title');

        DB::table('courses')->where('id', $course->id)->update([
            'title' => 'Raw updated title',
        ]);
        $this->get(route('courses.index'))
            ->assertSee('Raw updated title')
            ->assertDontSee('Cached title');

        $course->refresh()->update(['title' => 'Fresh title']);
        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Fresh title')
            ->assertDontSee('Raw updated title');
    }

    public function test_course_catalog_search_filters_by_title_and_validates_query_length(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        foreach (['Laravel Basics', 'PHP Testing'] as $title) {
            Course::create([
                'teacher_id' => $teacher->id,
                'title' => $title,
                'description' => 'Course description',
                'price' => 0,
                'status' => 'published',
            ]);
        }

        $this->actingAs($student)
            ->get(route('courses.index', ['q' => 'Laravel']))
            ->assertOk()
            ->assertSee('Laravel Basics')
            ->assertDontSee('PHP Testing');

        $this->get(route('courses.index', ['q' => str_repeat('x', 256)]))
            ->assertSessionHasErrors('q');
    }

    public function test_course_catalog_only_includes_published_courses(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        foreach ([
            ['title' => 'Public course', 'status' => 'published'],
            ['title' => 'Draft course', 'status' => 'draft'],
        ] as $courseData) {
            Course::create([
                'teacher_id' => $teacher->id,
                'title' => $courseData['title'],
                'description' => 'Course description',
                'price' => 0,
                'status' => $courseData['status'],
            ]);
        }

        $this->actingAs($student)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Public course')
            ->assertDontSee('Draft course');
    }

    public function test_teacher_only_sees_course_management_actions_for_their_own_courses(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($owner, 'Public course', 'Course description');

        $this->actingAs($anotherTeacher)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee(route('courses.show', $course))
            ->assertDontSee(route('courses.edit', $course))
            ->assertDontSee('aria-label="حذف Public course"', false);

        $this->actingAs($owner)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee(route('courses.edit', $course))
            ->assertSee('aria-label="حذف Public course"', false);
    }

    public function test_course_catalog_paginates_and_keeps_search_filters_in_page_links(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        for ($index = 1; $index <= 13; $index++) {
            $this->createCourse($teacher, 'Laravel course '.$index, 'Course description');
        }

        $response = $this->actingAs($student)
            ->get(route('courses.index', ['q' => 'Laravel']))
            ->assertOk()
            ->assertSee('Laravel course 1')
            ->assertSee('إجمالي الكورسات: 13');

        $this->assertStringContainsString('q=Laravel', $response->getContent());
        $this->assertStringContainsString('page=2', $response->getContent());
    }

    public function test_student_can_search_published_courses_and_teacher_names_without_exposing_emails(): void
    {
        $teacher = User::factory()->create([
            'name' => 'Searchable Teacher',
            'email' => 'private-teacher@example.test',
            'role' => 'teacher',
        ]);
        $student = User::factory()->create(['role' => 'student']);
        $this->createCourse($teacher, 'Searchable Course', 'Laravel lessons');
        $this->createCourse($teacher, 'Hidden Draft', 'Draft description', 'draft');

        $this->actingAs($student)
            ->get(route('search', ['q' => 'Laravel', 'type' => 'courses']))
            ->assertOk()
            ->assertSee('Searchable Course')
            ->assertDontSee('Hidden Draft');

        $this->get(route('search', ['q' => 'Searchable Teacher', 'type' => 'teachers']))
            ->assertOk()
            ->assertSee('Searchable Teacher')
            ->assertSee('Searchable Course')
            ->assertDontSee('private-teacher@example.test');
    }

    public function test_teacher_can_search_published_courses_and_their_own_drafts_only(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $anotherTeacher = User::factory()->create(['role' => 'teacher']);
        $this->createCourse($teacher, 'My Draft', 'My description', 'draft');
        $this->createCourse($anotherTeacher, 'Public Course', 'Public description');
        $this->createCourse($anotherTeacher, 'Private Draft', 'Private description', 'draft');

        $this->actingAs($teacher)
            ->get(route('search', ['q' => 'description', 'type' => 'courses']))
            ->assertOk()
            ->assertSee('My Draft')
            ->assertSee('Public Course')
            ->assertDontSee('Private Draft');
    }

    public function test_admin_can_search_all_users_by_email_and_courses_including_drafts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create([
            'name' => 'Private Teacher',
            'email' => 'teacher-search@example.test',
            'role' => 'teacher',
        ]);
        $this->createCourse($teacher, 'Admin Draft', 'Hidden from students', 'draft');

        $this->actingAs($admin)
            ->get(route('search', ['q' => 'teacher-search@example.test', 'type' => 'users']))
            ->assertOk()
            ->assertSee('Private Teacher')
            ->assertSee('teacher-search@example.test');

        $this->get(route('search', ['q' => 'Admin Draft', 'type' => 'courses']))
            ->assertOk()
            ->assertSee('Admin Draft')
            ->assertSee('مسودة');
    }

    public function test_users_cannot_search_types_not_allowed_for_their_role(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($student)
            ->get(route('search', ['q' => 'anything', 'type' => 'users']))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('search', ['q' => 'anything', 'type' => 'teachers']))
            ->assertForbidden();
    }

    private function createCourse(
        User $teacher,
        string $title,
        string $description,
        string $status = 'published',
    ): Course {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => $title,
            'description' => $description,
            'price' => 0,
            'status' => $status,
        ]);
    }
}
