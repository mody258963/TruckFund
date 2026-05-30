<?php

namespace App\Policies;

use App\Models\User;
use App\Services\UserHierarchyService;

class UserPolicy
{
    public function __construct(protected UserHierarchyService $hierarchy) {}

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->role->managesUsers();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $this->hierarchy->canViewUser($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->role->managesUsers();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || ($user->role->managesUsers() && $this->hierarchy->canViewUser($user, $model));
    }
}
