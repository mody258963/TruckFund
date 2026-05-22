<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Models\FinanceApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunCreditChecksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $appId) {}

    public function handle(): void
    {
        $app = FinanceApplication::query()->find($this->appId);

        if (! $app || $app->status !== ApplicationStatus::UnderReview) {
            return;
        }

        // Stub: DBR + iScore integration placeholder
        // In production, call external APIs and store results.
    }
}
