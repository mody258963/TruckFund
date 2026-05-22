<?php

namespace App\Enums;

enum DocType: int
{
    case NationalId = 1;
    case IncomeProof = 2;
    case CommercialReg = 3;
    case LandContract = 4;
    case AcceptancePaper = 5;
    case Other = 99;

    public function label(): string
    {
        return __('enums.doc_type.'.$this->name);
    }
}
