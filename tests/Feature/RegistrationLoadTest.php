<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrationLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_two_hundred_registration_attempts_from_one_ip_are_bounded(): void
    {
        Queue::fake();
        $statusCounts = [];
        $startedAt = hrtime(true);

        for ($attempt = 1; $attempt <= 200; $attempt++) {
            $response = $this->post(route('users.store'), [
                'name' => 'Load Test Student',
                'email' => "load-test-{$attempt}@example.test",
                'student_phone' => '01000000000',
                'parent_phone' => '01100000000',
                'education_stage' => 'secondary',
                'education_grade' => '1',
                'education_system' => 'old',
                'education_type' => 'general',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $statusCounts[$response->getStatusCode()] =
                ($statusCounts[$response->getStatusCode()] ?? 0) + 1;
        }

        $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

        $this->assertSame(5, $statusCounts[302] ?? 0, json_encode($statusCounts));
        $this->assertSame(195, $statusCounts[429] ?? 0, json_encode($statusCounts));
        $this->assertSame(5, User::query()->where('email', 'like', 'load-test-%@example.test')->count());
        $this->assertLessThan(
            30000,
            $elapsedMilliseconds,
            sprintf('The 200-request local abuse simulation took %.0f ms.', $elapsedMilliseconds),
        );
    }
}
