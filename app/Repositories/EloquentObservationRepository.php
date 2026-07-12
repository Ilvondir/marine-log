<?php

namespace App\Repositories;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Models\Observation;
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
     * Update an existing observation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Observation
    {
        try {
            $observation = Observation::query()->findOrFail($id);
            $observation->update($data);

            return $observation->fresh();
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'update',
                'operation' => 'update',
                'entity_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete an observation by its ID.
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): void
    {
        try {
            $observation = Observation::query()->findOrFail($id);
            $observation->delete();
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'delete',
                'operation' => 'delete',
                'entity_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
