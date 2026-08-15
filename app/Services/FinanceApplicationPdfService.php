<?php

namespace App\Services;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Models\FinanceApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class FinanceApplicationPdfService
{
    public function __construct(
        protected FinanceApplicationRepositoryInterface $applications,
        protected ImageStorageService $images,
    ) {}

    public function canGenerate(FinanceApplication $application): bool
    {
        return $application->financial_merchant_id
            && $application->auto_product_id
            && $application->financial_product_id
            && $application->total_truck_price !== null
            && $application->down_payment !== null
            && $application->total_loan_amount !== null;
    }

    public function loadForPdf(string $appId): FinanceApplication
    {
        return $this->applications->findWithRelations($appId)
            ?? abort(404);
    }

    /** @return Collection<int, array{label: string, path: string, is_image: bool, data_uri: ?string}> */
    public function collectAttachments(FinanceApplication $application): Collection
    {
        $attachments = collect();

        $customer = $application->customer;
        if ($customer?->identification) {
            $ident = $customer->identification;
            foreach ([
                ['label' => __('customers.id_front'), 'path' => $ident->id_front_url],
                ['label' => __('customers.id_back'), 'path' => $ident->id_back_url],
            ] as $item) {
                if ($item['path']) {
                    $attachments->push($this->attachmentEntry($item['label'], $item['path']));
                }
            }
        }

        if ($customer) {
            foreach ($customer->documents as $doc) {
                $attachments->push($this->attachmentEntry($doc->doc_type->label(), $doc->file_url));
            }
        }

        foreach ($application->applicationDocuments as $doc) {
            $attachments->push($this->attachmentEntry($doc->doc_type->label(), $doc->file_url));
        }

        return $attachments->filter(fn (array $entry) => $entry['path'] !== '')->values();
    }

    public function download(FinanceApplication $application): Response
    {
        $attachments = $this->collectAttachments($application);
        $filename = $application->app_number.'-package.pdf';

        return Pdf::loadView('pdf.finance-application', [
            'application' => $application,
            'customer' => $application->customer,
            'attachments' => $attachments,
            'generatedAt' => now(),
        ])
            ->setPaper('a4')
            ->download($filename);
    }

    /** @return array{label: string, path: string, is_image: bool, data_uri: ?string} */
    protected function attachmentEntry(string $label, ?string $path): array
    {
        $path = $path ?? '';
        $isImage = $path !== '' && $this->images->isImagePath($path);

        return [
            'label' => $label,
            'path' => $path,
            'is_image' => $isImage,
            'data_uri' => $isImage ? $this->images->dataUri($path) : null,
        ];
    }
}
