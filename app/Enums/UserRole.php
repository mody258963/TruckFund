<?php

namespace App\Enums;

enum UserRole: int
{
    case Admin = 1;
    case SalesAgent = 2;
    case FinanceOfficer = 3;
    case MerchantAgent = 4;

    public function label(): string
    {
        return __('enums.user_role.'.$this->name);
    }
}
