<?php

namespace App\Policies;

use App\Models\Freelancer;
use App\Models\User;

class FreelancerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isCrmRole();
    }

    public function view(User $user, Freelancer $freelancer): bool
    {
        return $user->role->isCrmRole();
    }

    public function create(User $user): bool
    {
        return $user->role->isCrmRole();
    }

    public function update(User $user, Freelancer $freelancer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ! $freelancer->is_locked;
    }
}
