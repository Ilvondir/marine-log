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
        if (app()->environment('production')) {
            $this->command?->warn('Skipping seed in production.');

            return;
        }

        Role::query()->firstOrCreate([
            'name' => Role::USER,
        ]);

        Role::query()->firstOrCreate([
            'name' => Role::ADMIN,
        ]);

        User::factory()
            ->admin()
            ->create([
                'name' => 'MarineLog Admin',
                'email' => 'admin@marinelog.test',
            ]);
    }
}
