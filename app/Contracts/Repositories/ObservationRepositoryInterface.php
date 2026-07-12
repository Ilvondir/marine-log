<?php

namespace App\Contracts\Repositories;

use App\Models\Observation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * Paginate published observations, newest first.
     *
     * @return LengthAwarePaginator<Observation>
     */
    public function paginatePublished(int $perPage = 12): LengthAwarePaginator;

    /**
     * Find a published observation by its ID.
     *
     * @throws ModelNotFoundException
     */
    public function findPublishedById(int $id): Observation;

    /**
     * Update an existing observation.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Observation;

    /**
     * Delete an observation by its ID.
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): void;
}
