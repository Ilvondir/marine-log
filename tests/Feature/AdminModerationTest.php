<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => Role::ADMIN]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createRegularUser(): User
    {
        return User::factory()->create();
    }

    private function createBlockedUser(): User
    {
        return User::factory()->create(['blocked_at' => now()]);
    }

    private function createObservation(?User $user = null): Observation
    {
        return Observation::factory()->create([
            'user_id' => $user?->id ?? User::factory()->create()->id,
            'published_at' => now(),
        ]);
    }

    // === ACCESS MATRIX: ADMIN OBSERVATIONS ===

    public function test_guest_cannot_access_admin_observations(): void
    {
        $response = $this->get(route('admin.observations.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_observations(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('admin.observations.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_observations(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.observations.index'));

        $response->assertOk();
    }

    // === ACCESS MATRIX: ADMIN USERS ===

    public function test_guest_cannot_access_admin_users(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_users(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_users(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
    }

    // === ACCESS MATRIX: MODERATION ACTIONS ===

    public function test_regular_user_cannot_delete_observation_via_admin(): void
    {
        $user = $this->createRegularUser();
        $observation = $this->createObservation();

        $response = $this->actingAs($user)->delete(route('admin.observations.destroy', $observation));

        $response->assertForbidden();
        $this->assertDatabaseHas('observations', ['id' => $observation->id]);
    }

    public function test_regular_user_cannot_unpublish_observation(): void
    {
        $user = $this->createRegularUser();
        $observation = $this->createObservation();

        $response = $this->actingAs($user)->patch(route('admin.observations.unpublish', $observation));

        $response->assertForbidden();
        $this->assertNotNull($observation->fresh()->published_at);
    }

    public function test_regular_user_cannot_block_user(): void
    {
        $user = $this->createRegularUser();
        $target = $this->createRegularUser();

        $response = $this->actingAs($user)->patch(route('admin.users.block', $target));

        $response->assertForbidden();
        $this->assertNull($target->fresh()->blocked_at);
    }

    // === ADMIN MODERATION ACTIONS WORK ===

    public function test_admin_can_delete_any_observation(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createRegularUser();
        $observation = $this->createObservation($user);

        $response = $this->actingAs($admin)->delete(route('admin.observations.destroy', $observation));

        $response->assertRedirect(route('admin.observations.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('observations', ['id' => $observation->id]);
    }

    public function test_admin_can_unpublish_observation(): void
    {
        $admin = $this->createAdmin();
        $observation = $this->createObservation();

        $this->assertNotNull($observation->published_at);

        $response = $this->actingAs($admin)->patch(route('admin.observations.unpublish', $observation));

        $response->assertRedirect(route('admin.observations.index'));
        $response->assertSessionHas('success');
        $this->assertNull($observation->fresh()->published_at);
    }

    // === SERVICE GUARDS ===

    public function test_admin_cannot_block_themselves(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->patch(route('admin.users.block', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Cannot block your own account.');
        $this->assertNull($admin->fresh()->blocked_at);
    }

    public function test_admin_cannot_block_another_admin(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();

        $response = $this->actingAs($admin)->patch(route('admin.users.block', $otherAdmin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Cannot block an admin account.');
        $this->assertNull($otherAdmin->fresh()->blocked_at);
    }

    public function test_admin_can_block_regular_user(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createRegularUser();

        $response = $this->actingAs($admin)->patch(route('admin.users.block', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertNotNull($user->fresh()->blocked_at);
    }

    public function test_admin_can_unblock_user(): void
    {
        $admin = $this->createAdmin();
        $blockedUser = $this->createBlockedUser();

        $response = $this->actingAs($admin)->patch(route('admin.users.unblock', $blockedUser));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertNull($blockedUser->fresh()->blocked_at);
    }

    // === BLOCKED USER BEHAVIOR ===

    public function test_blocked_user_is_forced_to_logout(): void
    {
        $user = $this->createBlockedUser();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_blocked_user_sees_error_message(): void
    {
        $user = $this->createBlockedUser();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Your account has been blocked.');
    }
}
