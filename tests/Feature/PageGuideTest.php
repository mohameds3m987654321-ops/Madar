<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_lists_page_summaries_and_video_scripts(): void
    {
        $guidePages = config('page_guide.pages');

        $this->assertGreaterThanOrEqual(25, count($guidePages));

        $this->get(route('guide'))
            ->assertOk()
            ->assertSee('دليل استخدام منصة مدار')
            ->assertSee('هذه الصفحة توفر النصوص والملخصات داخل المنصة')
            ->assertSee('نص مقترح لتعليق الفيديو')
            ->assertSee('لوحة المدير')
            ->assertSee('مشاهدة الدرس')
            ->assertSee('تسجيل الدخول');
    }

    public function test_each_page_guide_matches_an_existing_named_route(): void
    {
        foreach (array_keys(config('page_guide.pages')) as $routeName) {
            $this->assertNotNull(
                Route::getRoutes()->getByName($routeName),
                "The page guide references missing route [{$routeName}].",
            );
        }
    }

    public function test_homepage_does_not_load_the_removed_contextual_page_guide(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(asset('js/page-guide.js'), false)
            ->assertDontSee('name="madar-page-guide-title"', false)
            ->assertSee('دليل الاستخدام', false)
            ->assertDontSee('window.madarPageGuide', false);
    }

    public function test_authenticated_pages_do_not_load_the_removed_contextual_page_guide(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('users.profile'))
            ->assertOk()
            ->assertDontSee(asset('js/page-guide.js'), false)
            ->assertDontSee('name="madar-page-guide-title"', false)
            ->assertDontSee('window.madarPageGuide', false);
    }
}
