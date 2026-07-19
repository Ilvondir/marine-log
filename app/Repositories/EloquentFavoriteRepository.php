<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\FavoriteRepositoryInterface;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class EloquentFavoriteRepository implements FavoriteRepositoryInterface
{
    /**
     * Toggle favorite state. Returns true if added, false if removed.
     */
    public function toggle(int $userId, int $observationId): bool
    {
        try {
            $user = User::query()->findOrFail($userId);
            $changes = $user->favorites()->toggle($observationId);

            return ! empty($changes['attached']);
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'toggle',
                'operation' => 'toggle',
                'entity_id' => $observationId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a user has favorited an observation.
     */
    public function isFavorited(int $userId, int $observationId): bool
    {
        try {
            $user = User::query()->findOrFail($userId);

            return $user->favorites()->where('observation_id', $observationId)->exists();
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'isFavorited',
                'operation' => 'select',
                'entity_id' => $observationId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Count how many users favorited an observation.
     */
    public function countForObservation(int $observationId): int
    {
        try {
            return User::query()
                ->whereHas('favorites', fn ($q) => $q->where('observation_id', $observationId))
                ->count();
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'countForObservation',
                'operation' => 'select',
                'entity_id' => $observationId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Paginate observations favorited by a user, newest first.
     */
    public function paginateByUser(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        try {
            $user = User::query()->findOrFail($userId);

            /** @var LengthAwarePaginator<int, Observation> */
            return $user->favorites()
                ->with('photos')
                ->orderByPivot('created_at', 'desc')
                ->paginate($perPage);
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'paginateByUser',
                'operation' => 'select',
                'entity_id' => $userId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
