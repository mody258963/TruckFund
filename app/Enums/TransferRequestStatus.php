<?php

namespace App\Enums;

enum TransferRequestStatus: int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;

    public function label(): string
    {
        return __('enums.transfer_status.'.$this->name);
    }
}
