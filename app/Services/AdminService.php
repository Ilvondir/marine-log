<?php

namespace App\Services;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Observation;
use App\Models\User;

class AdminService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ObservationRepositoryInterface $observationRepository,
    ) {}

    /**
     * Block a user account by setting blocked_at timestamp.
     *
     * @throws \InvalidArgumentException if target is an admin or is the requesting admin
     */
    public function blockUser(User $admin, User $target): User
    {
        if ($admin->id === $target->id) {
            throw new \InvalidArgumentException('Cannot block your own account.');
        }

        if ($target->isAdmin()) {
            throw new \InvalidArgumentException('Cannot block an admin account.');
        }

        return $this->userRepository->update($target->id, [
            'blocked_at' => now(),
        ]);
    }

    /**
     * Unblock a user account by clearing blocked_at timestamp.
     */
    public function unblockUser(User $target): User
    {
        return $this->userRepository->update($target->id, [
            'blocked_at' => null,
        ]);
    }

    /**
     * Unpublish an observation by clearing published_at timestamp.
     */
    public function unpublishObservation(int $observationId): Observation
    {
        return $this->observationRepository->update($observationId, [
            'published_at' => null,
        ]);
    }
}
