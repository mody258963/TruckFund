<?php

namespace App\Enums;

enum CommType: int
{
    case Note = 1;
    case Call = 2;
    case Email = 3;
    case Sms = 4;

    public function label(): string
    {
        return __('enums.comm_type.'.$this->name);
    }
}
