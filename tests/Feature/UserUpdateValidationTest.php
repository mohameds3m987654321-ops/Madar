<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_another_users_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->put(route('users.update', $student), [
                'name' => $student->name,
                'email' => $student->email,
                'role' => 'teacher',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'teacher',
        ]);
    }

    public function test_role_must_be_one_of_the_supported_user_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->put(route('users.update', $student), [
                'name' => $student->name,
                'email' => $student->email,
                'role' => 'superuser',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'student',
        ]);
    }

    public function test_admin_cannot_change_their_own_role_through_user_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'student',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);

        $this->post(route('users.makeStudent', $admin))
            ->assertSessionHasErrors('user');
        $this->post(route('make-teacher', $admin))
            ->assertSessionHasErrors('user');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_list_filters_are_validated_and_applied(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'name' => 'Matching student',
            'email' => 'matching@example.test',
            'role' => 'student',
        ]);
        User::factory()->create([
            'name' => 'Other teacher',
            'email' => 'other@example.test',
            'role' => 'teacher',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index', [
                'q' => 'matching@example.test',
                'role' => 'student',
            ]))
            ->assertOk()
            ->assertSee('matching@example.test')
            ->assertDontSee('other@example.test');

        $this->get(route('users.index', ['role' => 'unknown']))
            ->assertSessionHasErrors('role');
    }
}
