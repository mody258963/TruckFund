<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FinanceApplication;
use App\Models\User;

class FinanceApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::Admin,
            UserRole::FinanceOfficer,
            UserRole::MerchantAgent,
        ], true);
    }

    public function view(User $user, FinanceApplication $application): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::FinanceOfficer], true);
    }

    public function update(User $user, FinanceApplication $application): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::FinanceOfficer], true);
    }

    public function decide(User $user, FinanceApplication $application): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::FinanceOfficer], true);
    }

    /** External funder: set review status + feedback only. */
    public function reviewFunder(User $user, FinanceApplication $application): bool
    {
        return in_array($user->role, [
            UserRole::Admin,
            UserRole::FinanceOfficer,
            UserRole::MerchantAgent,
        ], true);
    }
}
