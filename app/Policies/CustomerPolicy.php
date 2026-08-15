<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }
}
