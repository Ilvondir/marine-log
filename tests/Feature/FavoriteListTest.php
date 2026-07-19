<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_favorites_page(): void
    {
        $response = $this->get(route('observations.favorites'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_their_favorites(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['published_at' => now()]);
        $user->favorites()->attach($observation);

        $response = $this->actingAs($user)->get(route('observations.favorites'));

        $response->assertOk();
        $response->assertSee($observation->species);
    }

    public function test_favorites_page_shows_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('observations.favorites'));

        $response->assertOk();
        $response->assertSee('You have not favorited any observations yet.');
    }

    public function test_favorites_count_visible_to_guest_on_feed(): void
    {
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['published_at' => now()]);
        $user->favorites()->attach($observation);

        $response = $this->get(route('observations.index'));

        $response->assertOk();
        $response->assertSee('(1)');
    }
}
