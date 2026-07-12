<?php

namespace App\Policies;

use App\Models\Observation;
use App\Models\User;

class ObservationPolicy
{
    /**
     * Determine if the user can update the observation.
     */
    public function update(User $user, Observation $observation): bool
    {
        return $user->id === $observation->user_id;
    }

    /**
     * Determine if the user can delete the observation.
     */
    public function delete(User $user, Observation $observation): bool
    {
        return $user->id === $observation->user_id;
    }
}
