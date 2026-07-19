<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\FavoriteRepositoryInterface;
use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Models\Observation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepositoryInterface $favoriteRepository,
        private readonly ObservationRepositoryInterface $observationRepository,
    ) {}

    /**
     * Toggle favorite state for a user on a published observation.
     *
     * @return array{favorited: bool, count: int}
     *
     * @throws \InvalidArgumentException if observation is not published
     */
    public function toggleFavorite(int $userId, int $observationId): array
    {
        $observation = $this->observationRepository->findById($observationId);

        if ($observation->published_at === null) {
            throw new \InvalidArgumentException('Cannot favorite an unpublished observation.');
        }

        $favorited = $this->favoriteRepository->toggle($userId, $observationId);
        $count = $this->favoriteRepository->countForObservation($observationId);

        return ['favorited' => $favorited, 'count' => $count];
    }

    /**
     * Check if a user has favorited an observation.
     */
    public function isFavorited(int $userId, int $observationId): bool
    {
        return $this->favoriteRepository->isFavorited($userId, $observationId);
    }

    /**
     * Get the favorites count for an observation.
     */
    public function getFavoritesCount(int $observationId): int
    {
        return $this->favoriteRepository->countForObservation($observationId);
    }

    /**
     * Get paginated list of user's favorited observations.
     *
     * @return LengthAwarePaginator<int, Observation>
     */
    public function getUserFavorites(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->favoriteRepository->paginateByUser($userId, $perPage);
    }
}
