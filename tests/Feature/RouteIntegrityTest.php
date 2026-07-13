<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // === PHASE 1: Route Resolution and Non-Shadowing ===

    public function test_all_observation_named_routes_resolve_to_expected_uris(): void
    {
        $this->assertEquals('/observations', route('observations.index', [], false));
        $this->assertEquals('/observations/create', route('observations.create', [], false));
        $this->assertEquals('/observations/my', route('observations.my', [], false));
        $this->assertEquals('/observations', route('observations.store', [], false));
        $this->assertEquals('/observations/1', route('observations.show', 1, false));
        $this->assertEquals('/observations/1/edit', route('observations.edit', 1, false));
        $this->assertEquals('/observations/1', route('observations.update', 1, false));
        $this->assertEquals('/observations/1', route('observations.destroy', 1, false));
    }

    public function test_create_route_is_not_captured_by_wildcard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/observations/create');

        $response->assertStatus(200);
        $response->assertSee('Publish a wildlife observation');
    }

    public function test_my_route_is_not_captured_by_wildcard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/observations/my');

        $response->assertStatus(200);
    }

    public function test_show_route_resolves_numeric_id_correctly(): void
    {
        $observation = Observation::factory()->create(['species' => 'Testus resolvus']);

        $response = $this->get("/observations/{$observation->id}");

        $response->assertStatus(200);
        $response->assertSee('Testus resolvus');
    }

    // === PHASE 2: Unpublished Access Hardening ===

    public function test_authenticated_user_cannot_view_unpublished_observation(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->unpublished()->create();

        $response = $this->actingAs($user)->get(route('observations.show', $observation));

        $response->assertStatus(404);
    }

    public function test_owner_cannot_view_own_unpublished_observation(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->unpublished()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('observations.show', $observation));

        $response->assertStatus(404);
    }

    public function test_admin_cannot_view_unpublished_observation(): void
    {
        $adminRole = Role::create(['name' => Role::ADMIN]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $observation = Observation::factory()->unpublished()->create();

        $response = $this->actingAs($admin)->get(route('observations.show', $observation));

        $response->assertStatus(404);
    }

    // === PHASE 3: Access Level Matrix ===

    public function test_guest_is_redirected_from_authenticated_routes(): void
    {
        $this->get('/observations/create')->assertRedirect(route('login'));
        $this->get('/observations/my')->assertRedirect(route('login'));
        $this->post('/observations')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_is_redirected_from_guest_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect(route('home'));
        $this->actingAs($user)->get('/register')->assertRedirect(route('home'));
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $adminRole = Role::create(['name' => Role::ADMIN]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }
}
