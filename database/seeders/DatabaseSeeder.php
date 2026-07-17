<?php

namespace Database\Seeders;

use App\Models\Role;
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
        Role::query()->firstOrCreate([
            'name' => Role::USER,
        ]);

        Role::query()->firstOrCreate([
            'name' => Role::ADMIN,
        ]);

        if (! User::query()->where('email', 'admin@marinelog.test')->exists()) {
            User::factory()
                ->admin()
                ->create([
                    'name' => 'MarineLog Admin',
                    'email' => 'admin@marinelog.test',
                ]);
        }

        if (! User::query()->where('email', 'user@marinelog.test')->exists()) {
            User::factory()->create([
                'name' => 'MarineLog User',
                'email' => 'user@marinelog.test',
            ]);
        }

        // E2E test users (used by Playwright tests in CI)
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'E2E Test User',
                'email' => 'test@example.com',
            ]);
        }

        if (! User::query()->where('email', 'idor-attacker@example.com')->exists()) {
            User::factory()->create([
                'name' => 'IDOR Attacker',
                'email' => 'idor-attacker@example.com',
            ]);
        }
    }
}
