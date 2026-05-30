<?php

namespace App\Enums;

enum ApplicationStatus: int
{
    case Draft = 0;
    case Submitted = 1;
    case UnderReview = 2;
    case Accepted = 3;
    case Rejected = 4;
    case Cancelled = 5;
    case BookingConfirmed = 6;
    case DocsUploaded = 7;
    case Completed = 8;

    public function label(): string
    {
        return __('enums.application_status.'.$this->name);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Submitted, self::UnderReview => 'amber',
            self::Accepted, self::BookingConfirmed, self::DocsUploaded, self::Completed => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'zinc',
        };
    }

    public const PIPELINE_STEPS = 6;

    /** User-facing step (1–6) in the finance application workflow. */
    public function pipelineStep(): int
    {
        return match ($this) {
            self::Draft => 1,
            self::Submitted, self::UnderReview => 2,
            self::Accepted, self::Rejected, self::Cancelled => 3,
            self::BookingConfirmed => 4,
            self::DocsUploaded => 5,
            self::Completed => 6,
        };
    }

    public function isPipelineFailure(): bool
    {
        return in_array($this, [self::Rejected, self::Cancelled], true);
    }
}
