<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_limited_to_five_attempts_per_minute_per_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('users.create'))
                ->post(route('users.store'), [])
                ->assertRedirect(route('users.create'));
        }

        $this->from(route('users.create'))
            ->post(route('users.store'), [])
            ->assertTooManyRequests();
    }
}
