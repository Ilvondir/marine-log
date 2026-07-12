<?php

namespace Tests\Unit;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Enums\ResourceType;
use App\Models\Observation;
use App\Models\Resource;
use App\Services\ObservationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function test_get_published_feed_returns_paginated_results(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->observationRepo
            ->shouldReceive('paginatePublished')
            ->once()
            ->with(12)
            ->andReturn($paginator);

        $result = $this->service->getPublishedFeed(12);

        $this->assertSame($paginator, $result);
    }

    public function test_find_published_by_id_throws_for_unpublished(): void
    {
        $this->observationRepo
            ->shouldReceive('findPublishedById')
            ->once()
            ->with(99)
            ->andThrow(new ModelNotFoundException);

        $this->expectException(ModelNotFoundException::class);

        $this->service->findPublishedById(99);
    }
}
