<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $name = config('admin.name');
        $email = config('admin.email');
        $password = config('admin.password');

        if (!is_string($name) || trim($name) === '' ||
            !is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
            !is_string($password) || trim($password) === '') {
            throw new RuntimeException(
                'Set ADMIN_NAME, ADMIN_EMAIL, and a strong ADMIN_PASSWORD before seeding the database.'
            );
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => 'admin',
                'image' => null,
            ]
        );

        if ($admin->role !== 'admin') {
            throw new RuntimeException('The configured ADMIN_EMAIL already belongs to a non-admin user.');
        }
    }
}
