<?php

namespace App\Repositories;

use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Model;
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
}
