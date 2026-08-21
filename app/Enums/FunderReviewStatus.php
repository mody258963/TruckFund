<?php

namespace App\Enums;

enum FunderReviewStatus: int
{
    case NeedsAction = 1;
    case Rejected = 2;
    case Accepted = 3;

    public function label(): string
    {
        return __('enums.funder_review_status.'.$this->name);
    }

    public function color(): string
    {
        return match ($this) {
            self::NeedsAction => 'amber',
            self::Rejected => 'red',
            self::Accepted => 'green',
        };
    }
}
