<?php

namespace App\Contracts\Repositories;

use App\Models\Observation;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface ObservationRepositoryInterface
{
    /**
     * Create a new observation.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Observation;

    /**
     * Find an observation by its ID.
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $id): Observation;
}
