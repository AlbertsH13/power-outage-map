<?php

namespace App\Policies;

use App\Models\Outage;
use App\Models\User;

class OutagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Outage $outage): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Outage $outage): bool
    {
        return $user->id === $outage->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Outage $outage): bool
    {
        return $user->id === $outage->user_id;
    }

    /**
     * Determine whether the user can manually close the outage report.
     * Only the owner who reported the outage can close it.
     */
    public function close(User $user, Outage $outage): bool
    {
        return $user->id === $outage->user_id;
    }
}
