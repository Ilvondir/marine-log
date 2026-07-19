<?php

namespace App\Contracts\Repositories;

use App\Models\Observation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FavoriteRepositoryInterface
{
    /**
     * Toggle favorite state. Returns true if added, false if removed.
     */
    public function toggle(int $userId, int $observationId): bool;

    /**
     * Check if a user has favorited an observation.
     */
    public function isFavorited(int $userId, int $observationId): bool;

    /**
     * Count how many users favorited an observation.
     */
    public function countForObservation(int $observationId): int;

    /**
     * Paginate observations favorited by a user, newest first.
     *
     * @return LengthAwarePaginator<Observation>
     */
    public function paginateByUser(int $userId, int $perPage = 12): LengthAwarePaginator;
}
