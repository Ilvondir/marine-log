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
}
