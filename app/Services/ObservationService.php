<?php

namespace App\Services;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Enums\ResourceType;
use App\Models\Observation;
use App\Models\Resource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * Get a paginated feed of published observations, newest first.
     *
     * @return LengthAwarePaginator<Observation>
     */
    public function getPublishedFeed(int $perPage = 12): LengthAwarePaginator
    {
        return $this->observationRepository->paginatePublished($perPage);
    }

    /**
     * Find a published observation by ID.
     */
    public function findPublishedById(int $id): Observation
    {
        return $this->observationRepository->findPublishedById($id);
    }

    /**
     * Find an observation by ID.
     */
    public function findById(int $id): Observation
    {
        return $this->observationRepository->findById($id);
    }

    /**
     * Get a paginated list of observations belonging to a user.
     *
     * @return LengthAwarePaginator<Observation>
     */
    public function getUserObservations(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->observationRepository->paginateByUser($userId, $perPage);
    }

    /**
     * Get a paginated list of all observations (published and unpublished).
     *
     * @return LengthAwarePaginator<Observation>
     */
    public function getAllObservations(int $perPage = 20): LengthAwarePaginator
    {
        return $this->observationRepository->paginateAll($perPage);
    }

    /**
     * Update an existing observation, optionally adding/removing media.
     *
     * @param  array<string, mixed>  $validatedData
     * @param  array<int, UploadedFile>  $newPhotos
     * @param  array<int, UploadedFile>  $newVideos
     * @param  array<int, int>  $removeResourceIds
     */
    public function updateObservation(
        Observation $observation,
        array $validatedData,
        array $newPhotos = [],
        array $newVideos = [],
        array $removeResourceIds = [],
    ): Observation {
        return DB::transaction(function () use ($observation, $validatedData, $newPhotos, $newVideos, $removeResourceIds): Observation {
            if ($removeResourceIds !== []) {
                $this->removeResources($observation, $removeResourceIds);
            }

            $updatedObservation = $this->observationRepository->update($observation->id, [
                'species' => $validatedData['species'],
                'observed_at' => $validatedData['observed_at'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'location_name' => $validatedData['location_name'],
                'description' => $validatedData['description'] ?? null,
                'water_temperature' => $validatedData['water_temperature'] ?? null,
                'depth_meters' => $validatedData['depth_meters'] ?? null,
                'weather' => $validatedData['weather'] ?? null,
            ]);

            $this->storeMedia($updatedObservation, $newPhotos, ResourceType::Photo);
            $this->storeMedia($updatedObservation, $newVideos, ResourceType::Video);

            return $updatedObservation;
        });
    }

    /**
     * Delete an observation and all its associated media files.
     */
    public function deleteObservation(Observation $observation): void
    {
        DB::transaction(function () use ($observation): void {
            $filePaths = $observation->resources()->pluck('path')->all();

            $this->resourceRepository->deleteForResourceable($observation);
            $this->observationRepository->delete($observation->id);

            foreach ($filePaths as $path) {
                Storage::disk('public')->delete($path);
            }

            $directory = "observations/{$observation->id}";
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }
        });
    }

    /**
     * Remove specific resources from an observation (scoped to prevent IDOR).
     *
     * @param  array<int, int>  $resourceIds
     */
    private function removeResources(Observation $observation, array $resourceIds): void
    {
        $resources = $observation->resources()
            ->whereIn('id', $resourceIds)
            ->get();

        /** @var resource $resource */
        foreach ($resources as $resource) {
            Storage::disk('public')->delete($resource->path);
            $this->resourceRepository->deleteById($resource->id);
        }
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
