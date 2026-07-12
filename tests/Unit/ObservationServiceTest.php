<?php

namespace Tests\Unit;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Enums\ResourceType;
use App\Models\Observation;
use App\Models\Resource;
use App\Services\ObservationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ObservationServiceTest extends TestCase
{
    private MockInterface $observationRepo;

    private MockInterface $resourceRepo;

    private ObservationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->observationRepo = Mockery::mock(ObservationRepositoryInterface::class);
        $this->resourceRepo = Mockery::mock(ResourceRepositoryInterface::class);

        $this->service = new ObservationService(
            $this->observationRepo,
            $this->resourceRepo,
        );
    }

    public function test_publish_observation_creates_record_and_media(): void
    {
        Storage::fake('public');

        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 42;

        $this->observationRepo
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data): bool {
                return $data['species'] === 'Tursiops truncatus'
                    && $data['user_id'] === 1
                    && $data['published_at'] !== null;
            }))
            ->andReturn($observation);

        $this->resourceRepo
            ->shouldReceive('createForResourceable')
            ->once()
            ->with($observation, Mockery::on(function (array $data): bool {
                return $data['type'] === ResourceType::Photo->value
                    && $data['sort_order'] === 0
                    && $data['mime_type'] === 'image/jpeg';
            }))
            ->andReturn(new Resource);

        $result = $this->service->publishObservation(
            userId: 1,
            validatedData: [
                'species' => 'Tursiops truncatus',
                'observed_at' => '2026-06-10 14:30:00',
                'latitude' => '25.0343000',
                'longitude' => '-77.3963000',
                'location_name' => 'Dolphin Bay',
            ],
            photos: [UploadedFile::fake()->image('dolphin.jpg', 800, 600)],
        );

        $this->assertSame($observation, $result);
        Storage::disk('public')->assertExists('observations/42');
    }

    public function test_publish_observation_stores_files_to_public_disk(): void
    {
        Storage::fake('public');

        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 7;

        $this->observationRepo
            ->shouldReceive('create')
            ->once()
            ->andReturn($observation);

        $this->resourceRepo
            ->shouldReceive('createForResourceable')
            ->times(3)
            ->andReturn(new Resource);

        $this->service->publishObservation(
            userId: 1,
            validatedData: [
                'species' => 'Manta birostris',
                'observed_at' => '2026-05-20 09:00:00',
                'latitude' => '-8.5000000',
                'longitude' => '115.5000000',
                'location_name' => 'Manta Point, Bali',
            ],
            photos: [
                UploadedFile::fake()->image('manta1.jpg'),
                UploadedFile::fake()->image('manta2.png'),
            ],
            videos: [
                UploadedFile::fake()->create('dive.mp4', 5000, 'video/mp4'),
            ],
        );

        $files = Storage::disk('public')->allFiles('observations/7');
        $this->assertCount(3, $files);
    }

    public function test_update_observation_modifies_fields(): void
    {
        Storage::fake('public');

        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 10;
        $observation->shouldReceive('resources->whereIn->get')->andReturn(collect());

        $updatedObservation = Mockery::mock(Observation::class)->makePartial();
        $updatedObservation->id = 10;

        $this->observationRepo
            ->shouldReceive('update')
            ->once()
            ->with(10, Mockery::on(function (array $data): bool {
                return $data['species'] === 'Octopus vulgaris'
                    && $data['location_name'] === 'Updated Bay';
            }))
            ->andReturn($updatedObservation);

        $result = $this->service->updateObservation(
            observation: $observation,
            validatedData: [
                'species' => 'Octopus vulgaris',
                'observed_at' => '2026-06-01 08:00:00',
                'latitude' => '36.0000000',
                'longitude' => '14.0000000',
                'location_name' => 'Updated Bay',
            ],
        );

        $this->assertSame($updatedObservation, $result);
    }

    public function test_update_observation_adds_new_media(): void
    {
        Storage::fake('public');

        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 15;
        $observation->shouldReceive('resources->whereIn->get')->andReturn(collect());

        $this->observationRepo
            ->shouldReceive('update')
            ->once()
            ->andReturn($observation);

        $this->resourceRepo
            ->shouldReceive('createForResourceable')
            ->twice()
            ->andReturn(new Resource);

        $this->service->updateObservation(
            observation: $observation,
            validatedData: [
                'species' => 'Hippocampus kuda',
                'observed_at' => '2026-05-15 12:00:00',
                'latitude' => '10.0000000',
                'longitude' => '100.0000000',
                'location_name' => 'Seahorse Cove',
            ],
            newPhotos: [
                UploadedFile::fake()->image('seahorse1.jpg'),
                UploadedFile::fake()->image('seahorse2.png'),
            ],
        );

        $files = Storage::disk('public')->allFiles('observations/15');
        $this->assertCount(2, $files);
    }

    public function test_update_observation_removes_specified_resources(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('observations/20/old.jpg', 'data');

        $resourceToRemove = Mockery::mock(Resource::class)->makePartial();
        $resourceToRemove->id = 99;
        $resourceToRemove->path = 'observations/20/old.jpg';

        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 20;

        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('whereIn')->with('id', [99])->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(collect([$resourceToRemove]));
        $observation->shouldReceive('resources')->andReturn($mockQuery);

        $this->resourceRepo
            ->shouldReceive('deleteById')
            ->once()
            ->with(99);

        $this->observationRepo
            ->shouldReceive('update')
            ->once()
            ->andReturn($observation);

        $this->service->updateObservation(
            observation: $observation,
            validatedData: [
                'species' => 'Rhincodon typus',
                'observed_at' => '2026-04-01 06:00:00',
                'latitude' => '-5.0000000',
                'longitude' => '39.0000000',
                'location_name' => 'Whale Shark Alley',
            ],
            removeResourceIds: [99],
        );

        Storage::disk('public')->assertMissing('observations/20/old.jpg');
    }

    public function test_delete_observation_removes_record_and_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('observations/30/photo.jpg', 'data');
        Storage::disk('public')->put('observations/30/video.mp4', 'data');

        $mockResourcesQuery = Mockery::mock();
        $mockResourcesQuery->shouldReceive('pluck')
            ->with('path')
            ->andReturn(collect(['observations/30/photo.jpg', 'observations/30/video.mp4']));

        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 30;
        $observation->shouldReceive('resources')->andReturn($mockResourcesQuery);

        $this->resourceRepo
            ->shouldReceive('deleteForResourceable')
            ->once()
            ->with($observation);

        $this->observationRepo
            ->shouldReceive('delete')
            ->once()
            ->with(30);

        $this->service->deleteObservation($observation);

        Storage::disk('public')->assertMissing('observations/30/photo.jpg');
        Storage::disk('public')->assertMissing('observations/30/video.mp4');
    }
}
