<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_privileged_login_requires_the_fortify_two_factor_challenge(): void
    {
        config(['fortify.require_two_factor' => true]);
        $user = $this->createTwoFactorAdmin();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHas('login.id', $user->id);

        $this->assertGuest();

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee(route('two-factor.login.store'), false)
            ->assertSee('recovery_code', false);

        $this->post(route('two-factor.login.store'), [
            'recovery_code' => 'recovery-code',
        ])->assertRedirect(route('users.profile'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(
            (string) $user->id,
            (string) session('two_factor.authenticated_user_id'),
        );

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_privileged_routes_require_a_verified_two_factor_session(): void
    {
        config(['fortify.require_two_factor' => true]);
        $user = $this->createTwoFactorAdmin();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_privileged_user_without_two_factor_setup_is_sent_to_setup(): void
    {
        config(['fortify.require_two_factor' => true]);
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('two-factor.setup'));

        $this->get(route('two-factor.setup'))
            ->assertOk()
            ->assertSee(route('two-factor.enable'), false);
    }

    public function test_privileged_user_can_enroll_and_confirm_two_factor_authentication(): void
    {
        config(['fortify.require_two_factor' => true]);
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $user->refresh();
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

        $this->get(route('two-factor.setup'))
            ->assertOk()
            ->assertSee('data:image/svg+xml;base64,', false);

        $this->post(route('two-factor.confirm'), [
            'code' => app(Google2FA::class)->getCurrentOtp($secret),
        ])->assertRedirect();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    private function createTwoFactorAdmin(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => 'correct-password',
        ]);

        $user->forceFill([
            'two_factor_secret' => Crypt::encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }
}
