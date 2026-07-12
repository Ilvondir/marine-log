<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Authentication service.
 *
 * Note: This service uses the Auth facade directly (session-coupled) as a
 * pragmatic choice for the web-only auth scaffold. Auth is inherently bound
 * to sessions in Laravel's architecture. If API/token auth is needed later,
 * extract credential verification into a separate, HTTP-independent service.
 */
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
                'password' => $password,
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
