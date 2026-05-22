<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $user->role === UserRole::SalesAgent;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->role === UserRole::SalesAgent;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $user->role === UserRole::SalesAgent;
    }
}
