<?php

namespace App\Actions;

use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CloseOutageAction
{
    public function execute(User $user, Outage $outage): Outage
    {
        Gate::forUser($user)->authorize('close', $outage);

        if ($outage->status !== OutageStatus::Active || $outage->expires_at <= now()) {
            throw ValidationException::withMessages([
                'outage' => 'This report is no longer active.',
            ]);
        }

        $outage->update([
            'status' => OutageStatus::Closed,
            'closed_at' => now(),
        ]);

        return $outage;
    }
}
