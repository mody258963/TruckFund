<?php

namespace App\Services;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Enums\ApplicationStatus;
use App\Enums\DocType;
use App\Enums\FunderReviewStatus;
use App\Jobs\RunCreditChecksJob;
use App\Models\ApplicationDocument;
use App\Models\FinanceApplication;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FinanceApplicationService
{
    public function __construct(
        protected FinanceApplicationRepositoryInterface $applications,
        protected CommunicationLogService $communicationLogs,
        protected ImageStorageService $images,
    ) {}

    public function createDraft(array $data): FinanceApplication
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->applications->create([
                    ...$data,
                    'app_number' => $this->applications->generateAppNumber(),
                    'status' => ApplicationStatus::Draft,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 3) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Unable to generate a finance application number.');
    }

    /**
     * @param  list<string>|null  $autoProductIds
     */
    public function update(FinanceApplication $app, array $data, ?array $autoProductIds = null): FinanceApplication
    {
        return DB::transaction(function () use ($app, $data, $autoProductIds) {
            if ($autoProductIds !== null) {
                $ids = array_values(array_unique(array_filter($autoProductIds)));
                $data['auto_product_id'] = $ids[0] ?? null;
            }

            $app = $this->applications->update($app, $data);

            if ($autoProductIds !== null) {
                $sync = [];
                foreach (array_values(array_unique(array_filter($autoProductIds))) as $index => $id) {
                    $sync[$id] = ['sort_order' => $index];
                }
                $app->autoProducts()->sync($sync);
            }

            return $this->applications->findWithRelations($app->app_id) ?? $app->fresh();
        });
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
            'reentry_due_at' => $this->reentryDueDate($date),
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

    /**
     * @param  list<UploadedFile>  $files
     * @return list<ApplicationDocument>
     */
    public function uploadDocuments(
        FinanceApplication $app,
        array $files,
        DocType $type,
        ?string $userId = null
    ): array {
        return array_map(
            fn (UploadedFile $file) => $this->uploadDocument($app, $file, $type, $userId),
            array_values($files),
        );
    }

    public function complete(FinanceApplication $app): FinanceApplication
    {
        $payload = ['status' => ApplicationStatus::Completed];

        if ($app->reentry_due_at === null) {
            $payload['reentry_due_at'] = $this->reentryDueDate(now()->toDateString());
        }

        return $this->applications->update($app, $payload);
    }

    public function saveFunderReview(
        FinanceApplication $app,
        FunderReviewStatus $status,
        string $feedback,
        User $reviewer,
    ): FinanceApplication {
        return $this->applications->update($app, [
            'funder_status' => $status,
            'funder_feedback' => $feedback,
            'funder_reviewed_at' => now(),
            'funder_reviewed_by' => $reviewer->user_id,
        ]);
    }

    protected function reentryDueDate(string $fromDate): string
    {
        return \Carbon\Carbon::parse($fromDate)->addMonths(2)->toDateString();
    }
}
