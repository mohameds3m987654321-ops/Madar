<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_user_is_logged_without_personal_information(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'User account created.'
                && isset($context['user_id'])
                && is_int($context['user_id'])
                && !isset($context['email'], $context['name']));

        User::factory()->create([
            'name' => 'Private User Name',
            'email' => 'private@example.test',
        ]);
    }
}
