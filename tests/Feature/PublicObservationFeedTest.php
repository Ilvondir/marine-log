<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicObservationFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_observation_feed(): void
    {
        $response = $this->get(route('observations.index'));

        $response->assertStatus(200);
        $response->assertSee('Observations');
    }

    public function test_feed_only_shows_published_observations(): void
    {
        $published = Observation::factory()->create(['species' => 'Manta birostris']);
        $unpublished = Observation::factory()->unpublished()->create(['species' => 'Octopus secretus']);

        $response = $this->get(route('observations.index'));

        $response->assertStatus(200);
        $response->assertSee('Manta birostris');
        $response->assertDontSee('Octopus secretus');
    }

    public function test_feed_is_paginated(): void
    {
        Observation::factory()->count(15)->create();

        $response = $this->get(route('observations.index'));

        $response->assertStatus(200);
        // Default pagination is 12 per page — page 2 should exist
        $response->assertSee('page=2');
    }

    public function test_feed_orders_by_newest_first(): void
    {
        $older = Observation::factory()->create([
            'species' => 'Old Species',
            'published_at' => now()->subDays(5),
        ]);
        $newer = Observation::factory()->create([
            'species' => 'New Species',
            'published_at' => now(),
        ]);

        $response = $this->get(route('observations.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['New Species', 'Old Species']);
    }

    public function test_guest_can_view_published_observation(): void
    {
        $observation = Observation::factory()->create(['species' => 'Rhincodon typus']);

        $response = $this->get(route('observations.show', $observation));

        $response->assertStatus(200);
        $response->assertSee('Rhincodon typus');
    }

    public function test_guest_cannot_view_unpublished_observation(): void
    {
        $observation = Observation::factory()->unpublished()->create();

        $response = $this->get(route('observations.show', $observation));

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_view_feed(): void
    {
        $user = User::factory()->create();
        Observation::factory()->create(['species' => 'Hippocampus kuda']);

        $response = $this->actingAs($user)->get(route('observations.index'));

        $response->assertStatus(200);
        $response->assertSee('Hippocampus kuda');
    }

    public function test_feed_shows_empty_state_when_no_observations(): void
    {
        $response = $this->get(route('observations.index'));

        $response->assertStatus(200);
        $response->assertSee('No observations yet');
    }

    public function test_observation_card_displays_species_and_location(): void
    {
        Observation::factory()->create([
            'species' => 'Tursiops truncatus',
            'location_name' => 'Dolphin Bay',
        ]);

        $response = $this->get(route('observations.index'));

        $response->assertStatus(200);
        $response->assertSee('Tursiops truncatus');
        $response->assertSee('Dolphin Bay');
    }
}
