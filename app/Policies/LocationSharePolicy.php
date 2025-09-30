<?php

namespace App\Policies;

use App\Enums\LocationParticipantRole;
use App\Enums\LocationParticipantStatus;
use App\Models\LocationShare;
use App\Models\User;

class LocationSharePolicy
{
    public function view(User $user, LocationShare $share): bool
    {
        if ($user->id === $share->owner_id) {
            return true;
        }

        return $share->participants()
            ->where('user_id', $user->id)
            ->where('status', LocationParticipantStatus::ACCEPTED->value)
            ->exists();
    }

    public function manage(User $user, LocationShare $share): bool
    {
        return $user->id === $share->owner_id;
    }

    public function manageParticipants(User $user, LocationShare $share): bool
    {
        return $user->id === $share->owner_id;
    }

    public function stop(User $user, LocationShare $share): bool
    {
        return $user->id === $share->owner_id;
    }

    public function pushLive(User $user, LocationShare $share): bool
    {
        if ($share->status->value !== 'active' || !$share->allow_live_tracking) {
            return false;
        }

        if ($user->id === $share->owner_id) {
            return true;
        }

        return $share->participants()
            ->where('user_id', $user->id)
            ->where('status', LocationParticipantStatus::ACCEPTED->value)
            ->where('role', LocationParticipantRole::TRACKER->value)
            ->exists();
    }

    public function stream(User $user, LocationShare $share): bool
    {
        return $this->view($user, $share);
    }
}