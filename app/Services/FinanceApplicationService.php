<?php

namespace App\Services;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Enums\ApplicationStatus;
use App\Enums\DocType;
use App\Jobs\RunCreditChecksJob;
use App\Models\ApplicationDocument;
use App\Models\FinanceApplication;
use Illuminate\Http\UploadedFile;

class FinanceApplicationService
{
    public function __construct(
        protected FinanceApplicationRepositoryInterface $applications,
        protected CommunicationLogService $communicationLogs,
        protected ImageStorageService $images,
    ) {}

    public function createDraft(array $data): FinanceApplication
    {
        return $this->applications->create([
            ...$data,
            'app_number' => $this->applications->generateAppNumber(),
            'status' => ApplicationStatus::Draft,
        ]);
    }

    public function update(FinanceApplication $app, array $data): FinanceApplication
    {
        return $this->applications->update($app, $data);
    }

    public function submitForReview(FinanceApplication $app): FinanceApplication
    {
        $app = $this->applications->update($app, ['status' => ApplicationStatus::Submitted]);
        $app = $this->applications->update($app, ['status' => ApplicationStatus::UnderReview]);
        RunCreditChecksJob::dispatch($app->app_id);

        return $app;
    }

    public function decide(FinanceApplication $app, ApplicationStatus $decision): FinanceApplication
    {
        if (! in_array($decision, [
            ApplicationStatus::Accepted,
            ApplicationStatus::Rejected,
            ApplicationStatus::Cancelled,
        ], true)) {
            throw new \InvalidArgumentException('Invalid decision status');
        }

        return $this->applications->update($app, ['status' => $decision]);
    }

    public function confirmBooking(FinanceApplication $app, string $date): FinanceApplication
    {
        return $this->applications->update($app, [
            'booking_effective_date' => $date,
            'status' => ApplicationStatus::BookingConfirmed,
        ]);
    }

    public function uploadDocument(
        FinanceApplication $app,
        UploadedFile $file,
        DocType $type,
        ?string $userId = null
    ): ApplicationDocument {
        $stored = $this->images->store($file, 'applications/'.$app->app_id);
        $doc = ApplicationDocument::query()->create([
            'app_id' => $app->app_id,
            'doc_type' => $type,
            'file_url' => $stored['path'],
            'uploaded_by' => $userId,
        ]);

        if ($type === DocType::AcceptancePaper) {
            $this->applications->update($app, ['status' => ApplicationStatus::DocsUploaded]);
        }

        return $doc;
    }

    public function complete(FinanceApplication $app): FinanceApplication
    {
        return $this->applications->update($app, ['status' => ApplicationStatus::Completed]);
    }
}
