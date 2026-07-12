<?php

namespace App\Contracts\Repositories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface ResourceRepositoryInterface
{
    /**
     * Create a resource record for a given resourceable model.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForResourceable(Model $resourceable, array $data): Resource;

    /**
     * Delete all resource records for a given resourceable model.
     */
    public function deleteForResourceable(Model $resourceable): void;

    /**
     * Delete a single resource by its ID.
     *
     * @throws ModelNotFoundException
     */
    public function deleteById(int $id): void;
}
