<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Track observation IDs created during tests for cleanup.
     *
     * @var array<int>
     */
    private array $createdObservationIds = [];

    protected function tearDown(): void
    {
        // Clean up any test files from the real public disk
        foreach ($this->createdObservationIds as $id) {
            $directory = "observations/{$id}";
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }
        }

        parent::tearDown();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'species' => 'Chelonia mydas',
            'observed_at' => '2026-06-15T10:30',
            'latitude' => '25.0343000',
            'longitude' => '-77.3963000',
            'location_name' => 'Test Reef',
        ], $overrides);
    }

    // === STORAGE SMOKE ===

    public function test_public_storage_symlink_exists(): void
    {
        $symlinkPath = public_path('storage');

        $this->assertTrue(
            is_link($symlinkPath) || is_dir($symlinkPath),
            "Public storage symlink/directory does not exist at: {$symlinkPath}. Run 'artisan storage:link'."
        );

        // Verify it resolves to the correct target
        $expectedTarget = storage_path('app/public');
        $actualTarget = is_link($symlinkPath) ? readlink($symlinkPath) : realpath($symlinkPath);

        $this->assertEquals(
            realpath($expectedTarget),
            realpath($actualTarget),
            'Storage symlink does not point to storage/app/public.'
        );
    }

    // === URL CHAIN ===

    public function test_uploaded_photo_is_accessible_via_public_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [UploadedFile::fake()->image('turtle.jpg', 800, 600)]]
        ));

        $response->assertRedirect();

        $observation = Observation::query()->latest('id')->first();
        $this->createdObservationIds[] = $observation->id;

        $photo = $observation->photos->first();
        $this->assertNotNull($photo, 'No photo resource was created.');

        // Verify file exists on the public disk
        Storage::disk('public')->assertExists($photo->path);

        // Verify the file is physically accessible through the symlink path
        // (In production, the web server serves this; in tests, we verify the filesystem chain)
        $symlinkBase = public_path('storage');
        $fullPath = $symlinkBase.'/'.$photo->path;

        $this->assertFileExists($fullPath, "Photo not accessible via symlink at: {$fullPath}");
        $this->assertNotEmpty(file_get_contents($fullPath), 'Photo file is empty.');

        // Verify the URL that views would generate points to the correct path
        $expectedUrl = '/storage/'.$photo->path;
        $assetUrl = asset('storage/'.$photo->path);
        $this->assertStringEndsWith($expectedUrl, $assetUrl);
    }

    public function test_stored_path_matches_expected_pattern(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [UploadedFile::fake()->image('coral.jpg', 800, 600)]]
        ));

        $response->assertRedirect();

        $observation = Observation::query()->latest('id')->first();
        $this->createdObservationIds[] = $observation->id;

        $photo = $observation->photos->first();

        // Path should match: observations/{observation_id}/{hash}.{ext}
        $this->assertMatchesRegularExpression(
            '/^observations\/'.$observation->id.'\/[a-zA-Z0-9]+\.(jpg|jpeg|png|webp)$/',
            $photo->path,
            'Stored path does not match expected pattern.'
        );

        // The URL the view generates should be deterministic
        $expectedUrlPath = '/storage/'.$photo->path;
        $assetUrl = asset('storage/'.$photo->path);
        $this->assertStringEndsWith($expectedUrlPath, $assetUrl);
    }

    public function test_deleted_observation_media_is_no_longer_accessible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('observations.store'), array_merge(
            $this->validData(),
            ['photos' => [UploadedFile::fake()->image('reef.jpg', 800, 600)]]
        ));

        $observation = Observation::query()->latest('id')->first();
        $this->createdObservationIds[] = $observation->id;

        $photo = $observation->photos->first();
        $symlinkBase = public_path('storage');
        $fullPath = $symlinkBase.'/'.$photo->path;

        // Verify accessible before delete (via filesystem / symlink)
        $this->assertFileExists($fullPath);

        // Delete the observation
        $this->actingAs($user)->delete(route('observations.destroy', $observation));

        // Verify file no longer exists after delete
        $this->assertFileDoesNotExist($fullPath, 'Photo file still exists after observation deletion.');
    }
}
