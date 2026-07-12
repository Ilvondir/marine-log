<?php

namespace App\Contracts\Repositories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Model;

interface ResourceRepositoryInterface
{
    /**
     * Create a resource record for a given resourceable model.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForResourceable(Model $resourceable, array $data): Resource;
}
