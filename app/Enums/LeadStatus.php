<?php

namespace App\Enums;

enum LeadStatus: int
{
    case New = 0;
    case Pending = 1;
    case NotReachable = 2;
    case Duplicate = 3;
    case ResolvedProfileCreated = 4;
    case ResolvedNotInterested = 5;
    case ResolvedNotQualified = 6;
    case ResolvedProductSold = 7;
    case ResolvedProductListed = 8;
    case CashNoLoan = 9;

    public function label(): string
    {
        return __('enums.lead_status.'.$this->name);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Pending => 'zinc',
            self::NotReachable => 'red',
            self::Duplicate => 'zinc',
            self::CashNoLoan => 'amber',
            default => 'green',
        };
    }
}
