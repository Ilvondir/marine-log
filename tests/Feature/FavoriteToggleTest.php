<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_toggle_favorite(): void
    {
        $observation = Observation::factory()->create(['published_at' => now()]);

        $response = $this->post(route('observations.favorite.toggle', $observation));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_favorite_published_observation(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['published_at' => now()]);

        $response = $this->actingAs($user)->postJson(route('observations.favorite.toggle', $observation));

        $response->assertOk();
        $response->assertJson(['favorited' => true, 'count' => 1]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'observation_id' => $observation->id,
        ]);
    }

    public function test_user_can_unfavorite_observation(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['published_at' => now()]);
        $user->favorites()->attach($observation);

        $response = $this->actingAs($user)->postJson(route('observations.favorite.toggle', $observation));

        $response->assertOk();
        $response->assertJson(['favorited' => false, 'count' => 0]);
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'observation_id' => $observation->id,
        ]);
    }

    public function test_toggle_returns_correct_count(): void
    {
        $observation = Observation::factory()->create(['published_at' => now()]);
        $otherUsers = User::factory()->count(3)->create();
        foreach ($otherUsers as $otherUser) {
            $otherUser->favorites()->attach($observation);
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('observations.favorite.toggle', $observation));

        $response->assertOk();
        $response->assertJson(['favorited' => true, 'count' => 4]);
    }

    public function test_cannot_favorite_unpublished_observation(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['published_at' => null]);

        $response = $this->actingAs($user)->postJson(route('observations.favorite.toggle', $observation));

        $response->assertStatus(422);
    }

    public function test_favorite_is_idempotent(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['published_at' => now()]);

        // First toggle: add
        $this->actingAs($user)->postJson(route('observations.favorite.toggle', $observation));
        // Second toggle: remove
        $this->actingAs($user)->postJson(route('observations.favorite.toggle', $observation));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'observation_id' => $observation->id,
        ]);
    }
}
