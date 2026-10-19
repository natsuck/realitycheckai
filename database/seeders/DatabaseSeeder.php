<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Example student user
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        // Demo guidance counselor / admin account for the analytics dashboard
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@realitycheck.test'],
            [
                'name' => 'Guidance Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->isAdmin()) {
            $admin->forceFill(['user_type' => 'admin'])->save();
        }

        $admin->forceFill([
            'privacy_consented_at' => $admin->privacy_consented_at ?? now(),
        ])->save();

        // Call your QuestionSeeder
        $this->call(QuestionSeeder::class);

        // Seed the student Resource Center content
        $this->call(ResourceSeeder::class);
    }
}
