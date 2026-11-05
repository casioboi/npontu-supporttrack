<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function view(User $user, Activity $activity): bool
    {
        return $user->isAdmin()
            || $activity->assigned_to === $user->id
            || $activity->assignees()->whereKey($user->id)->exists()
            || $activity->created_by === $user->id
            || $activity->status === 'Pending'
            || $activity->updates()->where('status', 'Pending')->exists();
    }

    public function update(User $user, Activity $activity): bool
    {
        return ! $user->isAdmin()
            && ($activity->assigned_to === $user->id
                || $activity->assignees()->whereKey($user->id)->exists()
                || $activity->created_by === $user->id
                || $activity->status === 'Pending');
    }
}
