<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishObservationTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'species' => 'Chelonia mydas',
            'observed_at' => '2026-06-15T10:30',
            'latitude' => '25.0343000',
            'longitude' => '-77.3963000',
            'location_name' => 'Nassau Reef',
            'description' => 'Spotted near the coral formation.',
            'water_temperature' => '26.5',
            'depth_meters' => '12.0',
            'weather' => 'Sunny',
        ], $overrides);
    }

    public function test_guest_cannot_access_create_form(): void
    {
        $response = $this->get(route('observations.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_observation(): void
    {
        $response = $this->post(route('observations.store'), $this->validData());

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_see_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('observations.create'));

        $response->assertStatus(200);
        $response->assertSee('Publish a wildlife observation');
    }

    public function test_user_can_publish_observation_with_required_fields(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData([
                'description' => null,
                'water_temperature' => null,
                'depth_meters' => null,
                'weather' => null,
            ]),
            ['photos' => [UploadedFile::fake()->image('turtle.jpg', 800, 600)]]
        ));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('observations', [
            'user_id' => $user->id,
            'species' => 'Chelonia mydas',
            'location_name' => 'Nassau Reef',
        ]);

        $observation = Observation::query()->first();
        $this->assertNotNull($observation->published_at);
        $this->assertCount(1, $observation->photos);

        Storage::disk('public')->assertExists($observation->photos->first()->path);
    }

    public function test_user_can_publish_observation_with_all_optional_fields(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            [
                'photos' => [
                    UploadedFile::fake()->image('photo1.jpg', 1200, 800),
                    UploadedFile::fake()->image('photo2.png', 800, 600),
                ],
                'videos' => [
                    UploadedFile::fake()->create('dive.mp4', 5000, 'video/mp4'),
                ],
            ]
        ));

        $response->assertRedirect();

        $observation = Observation::query()->first();
        $this->assertEquals('Chelonia mydas', $observation->species);
        $this->assertEquals('26.5', $observation->water_temperature);
        $this->assertEquals('12.0', $observation->depth_meters);
        $this->assertEquals('Sunny', $observation->weather);
        $this->assertEquals('Spotted near the coral formation.', $observation->description);
        $this->assertCount(2, $observation->photos);
        $this->assertCount(1, $observation->videos);
    }

    public function test_publish_fails_without_species(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['species' => '']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg')]]
        ));

        $response->assertSessionHasErrors('species');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_without_photo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), $this->validData());

        $response->assertSessionHasErrors('photos');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_future_date(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['observed_at' => '2030-01-01T12:00']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg')]]
        ));

        $response->assertSessionHasErrors('observed_at');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_invalid_coordinates(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['latitude' => '95.0000000']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg')]]
        ));

        $response->assertSessionHasErrors('latitude');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_oversized_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [UploadedFile::fake()->image('huge.jpg')->size(11000)]]
        ));

        $response->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_published_observation_is_stored_with_media(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [UploadedFile::fake()->image('reef.jpg', 1024, 768)]]
        ));

        $observation = Observation::query()->first();
        $resource = $observation->photos->first();

        $this->assertEquals('photo', $resource->type->value);
        $this->assertStringContainsString('observations/'.$observation->id, $resource->path);
        $this->assertEquals('image/jpeg', $resource->mime_type);
        $this->assertGreaterThan(0, $resource->size_bytes);
        $this->assertEquals(0, $resource->sort_order);
    }

    // === VALIDATION BOUNDARY TESTS ===

    public function test_publish_fails_with_invalid_longitude(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['longitude' => '181.0000000']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg')]]
        ));

        $response->assertSessionHasErrors('longitude');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_negative_out_of_range_latitude(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['latitude' => '-91.0000000']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg')]]
        ));

        $response->assertSessionHasErrors('latitude');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_accepts_boundary_coordinates(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['latitude' => '90.0000000', 'longitude' => '-180.0000000']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg', 800, 600)]]
        ));

        $response->assertSessionDoesntHaveErrors(['latitude', 'longitude']);
        $this->assertDatabaseCount('observations', 1);
    }

    public function test_publish_fails_with_invalid_photo_mime_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [UploadedFile::fake()->create('animation.gif', 500, 'image/gif')]]
        ));

        $response->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_oversized_video(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            [
                'photos' => [UploadedFile::fake()->image('photo.jpg', 800, 600)],
                'videos' => [UploadedFile::fake()->create('huge.mp4', 103000, 'video/mp4')],
            ]
        ));

        $response->assertSessionHasErrors('videos.0');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_invalid_video_mime_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            [
                'photos' => [UploadedFile::fake()->image('photo.jpg', 800, 600)],
                'videos' => [UploadedFile::fake()->create('clip.avi', 5000, 'video/x-msvideo')],
            ]
        ));

        $response->assertSessionHasErrors('videos.0');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_description_exceeding_max_length(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['description' => str_repeat('a', 5001)]),
            ['photos' => [UploadedFile::fake()->image('photo.jpg', 800, 600)]]
        ));

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_water_temperature_below_minimum(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['water_temperature' => '-6']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg', 800, 600)]]
        ));

        $response->assertSessionHasErrors('water_temperature');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_fails_with_depth_meters_exceeding_maximum(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(['depth_meters' => '501']),
            ['photos' => [UploadedFile::fake()->image('photo.jpg', 800, 600)]]
        ));

        $response->assertSessionHasErrors('depth_meters');
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_publish_accepts_webp_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [UploadedFile::fake()->image('coral.webp', 800, 600)]]
        ));

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('observations', 1);

        $observation = Observation::query()->first();
        $this->assertCount(1, $observation->photos);
    }

    public function test_publish_stores_multiple_photos_with_correct_sort_order(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [
                UploadedFile::fake()->image('first.jpg', 800, 600),
                UploadedFile::fake()->image('second.jpg', 800, 600),
                UploadedFile::fake()->image('third.jpg', 800, 600),
            ]]
        ));

        $observation = Observation::query()->first();
        $photos = $observation->photos()->orderBy('sort_order')->get();

        $this->assertCount(3, $photos);
        $this->assertEquals(0, $photos[0]->sort_order);
        $this->assertEquals(1, $photos[1]->sort_order);
        $this->assertEquals(2, $photos[2]->sort_order);
    }
}
