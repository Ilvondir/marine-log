<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_homepage_shows_auth_entry_points(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Create account')
            ->assertSee('Sign in')
            ->assertDontSee('Signed in as');
    }

    public function test_authenticated_homepage_shows_user_identity_and_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Signed in as')
            ->assertSee($user->name)
            ->assertSee($user->role?->name ?? Role::USER)
            ->assertDontSee('Create account')
            ->assertDontSee('Sign in');
    }

    public function test_user_can_register_sign_in_and_sign_out(): void
    {
        $password = 'password123';

        $this->post(route('register.store'), [
            'name' => 'Ocean Observer',
            'email' => 'observer@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect(route('home'));

        $user = User::query()->where('email', 'observer@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->role?->name === Role::USER);

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => 'observer@example.com',
            'password' => $password,
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_regular_users_are_denied_admin_only_access(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_seeded_admin_exists_and_can_access_admin_only_area(): void
    {
        $this->seed();

        $this->assertDatabaseHas('roles', [
            'name' => Role::ADMIN,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@marinelog.test',
        ]);

        $this->post(route('login.store'), [
            'email' => 'admin@marinelog.test',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $admin = User::query()->where('email', 'admin@marinelog.test')->firstOrFail();

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->isAdmin());

        $this->get(route('admin.dashboard'))->assertOk();
    }
}
