<?php

namespace App\Repositories;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Models\Observation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class EloquentObservationRepository implements ObservationRepositoryInterface
{
    /**
     * Create a new observation.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Observation
    {
        try {
            return Observation::query()->create($data);
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'create',
                'operation' => 'insert',
                'entity_id' => null,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find an observation by its ID.
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $id): Observation
    {
        try {
            /** @var Observation */
            return Observation::query()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning('Observation not found.', [
                'repository' => self::class,
                'method' => 'findById',
                'operation' => 'select',
                'entity_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Paginate published observations, newest first.
     *
     * Eager-loads the first photo for thumbnail display and includes
     * coordinates for map rendering.
     *
     * @return LengthAwarePaginator<Observation>
     */
    public function paginatePublished(int $perPage = 12): LengthAwarePaginator
    {
        try {
            return Observation::query()
                ->published()
                ->with('photos')
                ->latest('published_at')
                ->paginate($perPage);
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'paginatePublished',
                'operation' => 'select',
                'entity_id' => null,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find a published observation by its ID.
     *
     * Returns 404 for unpublished or non-existent observations.
     *
     * @throws ModelNotFoundException
     */
    public function findPublishedById(int $id): Observation
    {
        try {
            /** @var Observation */
            return Observation::query()
                ->published()
                ->with(['user', 'photos', 'videos'])
                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning('Published observation not found.', [
                'repository' => self::class,
                'method' => 'findPublishedById',
                'operation' => 'select',
                'entity_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
