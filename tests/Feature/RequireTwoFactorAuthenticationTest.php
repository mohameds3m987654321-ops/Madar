<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireTwoFactorAuthentication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequireTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_uses_cached_fortify_configuration(): void
    {
        config(['fortify.require_two_factor' => true]);

        $request = Request::create('/');
        $request->setUserResolver(
            fn () => User::factory()->create(['role' => 'admin'])
        );

        $response = (new RequireTwoFactorAuthentication)->handle(
            $request,
            fn () => response('next')
        );

        $this->assertSame(route('two-factor.setup'), $response->headers->get('Location'));
    }
}
