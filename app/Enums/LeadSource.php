<?php

namespace App\Enums;

enum LeadSource: int
{
    case AdminDashboard = 0;
    case SalesInput = 1;
    case ExternalApi = 2;
    case Referral = 3;
    case Import = 4;

    public function label(): string
    {
        return __('enums.lead_source.'.$this->name);
    }
}
