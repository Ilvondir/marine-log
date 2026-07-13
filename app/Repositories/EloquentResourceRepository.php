<?php

namespace App\Repositories;

use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class EloquentResourceRepository implements ResourceRepositoryInterface
{
    /**
     * Create a resource record for a given resourceable model.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForResourceable(Model $resourceable, array $data): Resource
    {
        try {
            /** @var resource */
            return $resourceable->morphMany(Resource::class, 'resourceable')->create($data);
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'createForResourceable',
                'operation' => 'insert',
                'entity_id' => $resourceable->getKey(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete all resource records for a given resourceable model.
     */
    public function deleteForResourceable(Model $resourceable): void
    {
        try {
            $resourceable->morphMany(Resource::class, 'resourceable')->delete();
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'deleteForResourceable',
                'operation' => 'delete',
                'entity_id' => $resourceable->getKey(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a single resource by its ID.
     *
     * @throws ModelNotFoundException
     */
    public function deleteById(int $id): void
    {
        try {
            $resource = Resource::query()->findOrFail($id);
            $resource->delete();
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'deleteById',
                'operation' => 'delete',
                'entity_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
