<?php

namespace App\Enums;

enum LeadValue: int
{
    case Low = 0;
    case Mid = 1;
    case High = 2;

    public function label(): string
    {
        return __('enums.lead_value.'.$this->name);
    }
}
