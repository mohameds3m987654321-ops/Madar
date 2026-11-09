<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_real_platform_summary_and_recent_records(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'preferred_theme' => 'dark',
        ]);
        $teacher = User::factory()->create([
            'name' => 'مدرس مدار',
            'role' => 'teacher',
        ]);
        $student = User::factory()->create([
            'name' => 'طالب مدار',
            'role' => 'student',
        ]);
        $published = $this->createCourse($teacher, 'دورة منشورة', 'published');
        $this->createCourse($teacher, 'مسودة الدورة', 'draft');
        CourseEnrollment::create([
            'course_id' => $published->id,
            'user_id' => $student->id,
            'payment_method' => 'free',
            'status' => 'paid',
            'amount_cents' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('أهلًا ' . $admin->name)
            ->assertSee('كل المستخدمين')
            ->assertSee('دورة منشورة')
            ->assertSee('مسودة الدورة')
            ->assertSee('مدرس مدار')
            ->assertSee('طالب مدار')
            ->assertSee('إدارة المستخدمين')
            ->assertSee(route('users.index'))
            ->assertSee('class="admin-management"', false)
            ->assertSee('data-theme="dark"', false)
            ->assertSee(asset('css/appearance.css'), false)
            ->assertSee('management-header', false)
            ->assertSee(asset('css/management-navigation.css'), false)
            ->assertSee('1', false);
    }

    public function test_admin_management_pages_share_the_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        foreach ([
            route('users.index'),
            route('users.edit', $student),
            route('courses.index'),
        ] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-management"', false)
                ->assertSee('management-header', false)
                ->assertSee(asset('css/management-navigation.css'), false);
        }
    }

    public function test_non_admin_cannot_open_admin_dashboard(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('users.profile'));
    }

    public function test_admin_can_reach_dashboard_from_their_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('users.profile'))
            ->assertOk()
            ->assertSee(route('admin.dashboard'))
            ->assertSee('لوحة المدير');
    }

    private function createCourse(User $teacher, string $title, string $status): Course
    {
        return Course::create([
            'teacher_id' => $teacher->id,
            'title' => $title,
            'description' => 'وصف الدورة.',
            'price' => 0,
            'status' => $status,
        ]);
    }
}
