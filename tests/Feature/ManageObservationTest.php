<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManageObservationTest extends TestCase
{
    use RefreshDatabase;

    private function createObservationWithPhoto(User $user): Observation
    {
        $observation = Observation::factory()->create(['user_id' => $user->id]);

        Resource::factory()->create([
            'resourceable_id' => $observation->id,
            'resourceable_type' => Observation::class,
            'path' => "observations/{$observation->id}/photo.jpg",
        ]);

        return $observation;
    }

    private function validUpdateData(array $overrides = []): array
    {
        return array_merge([
            'species' => 'Tursiops truncatus',
            'observed_at' => '2026-06-15T10:30',
            'latitude' => '25.0343000',
            'longitude' => '-77.3963000',
            'location_name' => 'Updated Reef',
            'description' => 'Updated description.',
            'water_temperature' => '24.5',
            'depth_meters' => '15.0',
            'weather' => 'Cloudy',
        ], $overrides);
    }

    // === EDIT ===

    public function test_guest_cannot_access_edit_form(): void
    {
        $observation = Observation::factory()->create();

        $response = $this->get(route('observations.edit', $observation));

        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_access_edit_form(): void
    {
        $user = User::factory()->create();
        $observation = $this->createObservationWithPhoto($user);

        $response = $this->actingAs($user)->get(route('observations.edit', $observation));

        $response->assertStatus(200);
        $response->assertSee('Edit your observation');
        $response->assertSee($observation->species);
    }

    public function test_non_owner_cannot_access_edit_form(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $observation = $this->createObservationWithPhoto($owner);

        $response = $this->actingAs($otherUser)->get(route('observations.edit', $observation));

        $response->assertStatus(403);
    }

    // === UPDATE ===

    public function test_owner_can_update_observation_fields(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $observation = $this->createObservationWithPhoto($user);

        $response = $this->actingAs($user)->put(
            route('observations.update', $observation),
            $this->validUpdateData()
        );

        $response->assertRedirect(route('observations.show', $observation));
        $response->assertSessionHas('success');

        $observation->refresh();
        $this->assertEquals('Tursiops truncatus', $observation->species);
        $this->assertEquals('Updated Reef', $observation->location_name);
        $this->assertEquals('Updated description.', $observation->description);
    }

    public function test_owner_can_add_new_photos_to_existing_observation(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $observation = $this->createObservationWithPhoto($user);

        $response = $this->actingAs($user)->put(
            route('observations.update', $observation),
            array_merge(
                $this->validUpdateData(),
                ['photos' => [UploadedFile::fake()->image('new_photo.jpg', 800, 600)]]
            )
        );

        $response->assertRedirect();
        $this->assertCount(2, $observation->fresh()->photos);
    }

    public function test_owner_can_remove_a_photo_from_observation(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['user_id' => $user->id]);

        // Create two photos so we can remove one and still have one remaining
        $photo1 = Resource::factory()->create([
            'resourceable_id' => $observation->id,
            'resourceable_type' => Observation::class,
            'path' => "observations/{$observation->id}/photo1.jpg",
        ]);
        $photo2 = Resource::factory()->create([
            'resourceable_id' => $observation->id,
            'resourceable_type' => Observation::class,
            'path' => "observations/{$observation->id}/photo2.jpg",
        ]);

        // Put a fake file on disk for the one we'll remove
        Storage::disk('public')->put($photo1->path, 'fake-image-data');

        $response = $this->actingAs($user)->put(
            route('observations.update', $observation),
            array_merge(
                $this->validUpdateData(),
                ['remove_resources' => [$photo1->id]]
            )
        );

        $response->assertRedirect();
        $this->assertCount(1, $observation->fresh()->photos);
        $this->assertDatabaseMissing('resources', ['id' => $photo1->id]);
        Storage::disk('public')->assertMissing($photo1->path);
    }

    public function test_update_fails_if_all_photos_would_be_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $observation = $this->createObservationWithPhoto($user);
        $photoId = $observation->photos->first()->id;

        $response = $this->actingAs($user)->put(
            route('observations.update', $observation),
            array_merge(
                $this->validUpdateData(),
                ['remove_resources' => [$photoId]]
            )
        );

        $response->assertSessionHasErrors('photos');
        // Photo should still exist
        $this->assertDatabaseHas('resources', ['id' => $photoId]);
    }

    public function test_non_owner_cannot_update_observation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $observation = $this->createObservationWithPhoto($owner);

        $response = $this->actingAs($otherUser)->put(
            route('observations.update', $observation),
            $this->validUpdateData()
        );

        $response->assertStatus(403);
    }

    // === DELETE ===

    public function test_owner_can_delete_observation(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $observation = $this->createObservationWithPhoto($user);

        $response = $this->actingAs($user)->delete(route('observations.destroy', $observation));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('observations', ['id' => $observation->id]);
        $this->assertDatabaseMissing('resources', ['resourceable_id' => $observation->id]);
    }

    public function test_non_owner_cannot_delete_observation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $observation = $this->createObservationWithPhoto($owner);

        $response = $this->actingAs($otherUser)->delete(route('observations.destroy', $observation));

        $response->assertStatus(403);
        $this->assertDatabaseHas('observations', ['id' => $observation->id]);
    }

    public function test_delete_removes_files_from_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $observation = Observation::factory()->create(['user_id' => $user->id]);

        $resource = Resource::factory()->create([
            'resourceable_id' => $observation->id,
            'resourceable_type' => Observation::class,
            'path' => "observations/{$observation->id}/reef.jpg",
        ]);

        Storage::disk('public')->put($resource->path, 'fake-image-content');
        Storage::disk('public')->assertExists($resource->path);

        $this->actingAs($user)->delete(route('observations.destroy', $observation));

        Storage::disk('public')->assertMissing($resource->path);
    }

    public function test_guest_cannot_delete_observation(): void
    {
        $observation = Observation::factory()->create();

        $response = $this->delete(route('observations.destroy', $observation));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('observations', ['id' => $observation->id]);
    }
}
