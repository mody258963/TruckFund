<?php

namespace App\Enums;

enum Gender: int
{
    case Male = 1;
    case Female = 2;

    public function label(): string
    {
        return __('enums.gender.'.$this->name);
    }
}
