<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user and sign them in.
     */
    public function register(string $name, string $email, string $password): User
    {
        $user = DB::transaction(function () use ($name, $email, $password): User {
            $userRole = Role::query()->firstOrCreate([
                'name' => Role::USER,
            ]);

            return User::query()->create([
                'role_id' => $userRole->id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        });

        Auth::login($user);

        return $user;
    }

    /**
     * Attempt to sign the user in.
     */
    public function authenticate(string $email, string $password): bool
    {
        if (! Auth::attempt([
            'email' => $email,
            'password' => $password,
        ])) {
            return false;
        }

        return true;
    }

    /**
     * Sign the current user out.
     */
    public function logout(): void
    {
        Auth::logout();
    }
}
