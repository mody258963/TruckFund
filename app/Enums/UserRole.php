<?php

namespace App\Enums;

enum UserRole: int
{
    case Admin = 1;
    case SalesAgent = 2;
    case FinanceOfficer = 3;
    case MerchantAgent = 4;
    case Manager = 5;
    case TeamLeader = 6;

    public function label(): string
    {
        return __('enums.user_role.'.$this->name);
    }

    public function isCrmRole(): bool
    {
        return in_array($this, [
            self::Admin,
            self::Manager,
            self::TeamLeader,
            self::SalesAgent,
        ], true);
    }

    public function canAssignLeads(): bool
    {
        return in_array($this, [self::Admin, self::Manager, self::TeamLeader], true);
    }

    public function canRequestLeadTransfer(): bool
    {
        return $this === self::SalesAgent;
    }

    public function managesUsers(): bool
    {
        return in_array($this, [self::Admin, self::Manager, self::TeamLeader], true);
    }
}
