<?php

namespace App\Services;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Enums\ResourceType;
use App\Models\Observation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ObservationService
{
    public function __construct(
        private readonly ObservationRepositoryInterface $observationRepository,
        private readonly ResourceRepositoryInterface $resourceRepository,
    ) {}

    /**
     * Publish a new observation with associated media files.
     *
     * @param  array<string, mixed>  $validatedData
     * @param  array<int, UploadedFile>  $photos
     * @param  array<int, UploadedFile>  $videos
     */
    public function publishObservation(int $userId, array $validatedData, array $photos, array $videos = []): Observation
    {
        return DB::transaction(function () use ($userId, $validatedData, $photos, $videos): Observation {
            $observation = $this->observationRepository->create([
                'user_id' => $userId,
                'species' => $validatedData['species'],
                'observed_at' => $validatedData['observed_at'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'location_name' => $validatedData['location_name'],
                'description' => $validatedData['description'] ?? null,
                'water_temperature' => $validatedData['water_temperature'] ?? null,
                'depth_meters' => $validatedData['depth_meters'] ?? null,
                'weather' => $validatedData['weather'] ?? null,
                'published_at' => now(),
            ]);

            $this->storeMedia($observation, $photos, ResourceType::Photo);
            $this->storeMedia($observation, $videos, ResourceType::Video);

            return $observation;
        });
    }

    /**
     * Find an observation by ID.
     */
    public function findById(int $id): Observation
    {
        return $this->observationRepository->findById($id);
    }

    /**
     * Store uploaded files and create resource records.
     *
     * @param  array<int, UploadedFile>  $files
     */
    private function storeMedia(Observation $observation, array $files, ResourceType $type): void
    {
        $directory = "observations/{$observation->id}";

        foreach ($files as $index => $file) {
            $path = Storage::disk('public')->putFile($directory, $file);

            $this->resourceRepository->createForResourceable($observation, [
                'type' => $type->value,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'sort_order' => $index,
            ]);
        }
    }
}
