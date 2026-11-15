<?php

namespace App\Policies;

use App\Models\Outage;
use App\Models\User;

class OutagePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Outage $outage): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Outage $outage): bool
    {
        return $user->id === $outage->user_id;
    }

    public function delete(User $user, Outage $outage): bool
    {
        return $user->id === $outage->user_id;
    }

    public function close(User $user, Outage $outage): bool
    {
        return $user->id === $outage->user_id;
    }
}
