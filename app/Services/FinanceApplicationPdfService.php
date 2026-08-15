<?php

namespace App\Services;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Models\FinanceApplication;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

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

    /** @return Collection<int, array{label: string, path: string, type: 'image'|'pdf'|'unsupported', absolute_path: ?string}> */
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

        return $attachments
            ->filter(fn (array $entry) => $entry['type'] !== 'unsupported' && $entry['absolute_path'] !== null)
            ->values();
    }

    public function download(FinanceApplication $application): Response
    {
        $originalLocale = app()->getLocale();

        try {
            app()->setLocale('ar');

            $attachments = $this->collectAttachments($application);
            $tempDirectory = storage_path('app/mpdf-temp');
            File::ensureDirectoryExists($tempDirectory);

            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'tempDir' => $tempDirectory,
                'default_font' => 'dejavusans',
                'directionality' => 'rtl',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);

            $pdf->SetTitle($application->app_number);

            // Each chunk is written separately so no single string approaches
            // pcre.backtrack_limit, which mPDF's HTML parser is bound by.
            $pdf->WriteHTML(
                view('pdf.finance-application-styles')->render(),
                HTMLParserMode::HEADER_CSS,
            );
            $pdf->WriteHTML(
                view('pdf.finance-application', [
                    'application' => $application,
                    'customer' => $application->customer,
                    'generatedAt' => now(),
                ])->render(),
                HTMLParserMode::HTML_BODY,
            );

            $this->appendImageAttachments($pdf, $attachments->where('type', 'image'));
            $this->appendPdfAttachments($pdf, $attachments->where('type', 'pdf'));

            $filename = $application->app_number.'-package.pdf';
            $contents = $pdf->Output($filename, Destination::STRING_RETURN);

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Content-Length' => (string) strlen($contents),
            ]);
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    /** @return array{label: string, path: string, type: 'image'|'pdf'|'unsupported', absolute_path: ?string} */
    protected function attachmentEntry(string $label, ?string $path): array
    {
        $path = $path ?? '';
        $type = match (true) {
            $path !== '' && $this->images->isImagePath($path) => 'image',
            (bool) preg_match('/\.pdf$/i', $path) => 'pdf',
            default => 'unsupported',
        };

        return [
            'label' => $label,
            'path' => $path,
            'type' => $type,
            'absolute_path' => $path !== '' ? $this->images->absolutePath($path) : null,
        ];
    }

    /** @param Collection<int, array{label: string, absolute_path: string}> $attachments */
    protected function appendImageAttachments(Mpdf $pdf, Collection $attachments): void
    {
        foreach ($attachments as $attachment) {
            $pdf->AddPage();
            $pdf->WriteHTML(
                view('pdf.finance-application-attachment', ['attachment' => $attachment])->render(),
                HTMLParserMode::HTML_BODY,
            );
        }
    }

    /** @param Collection<int, array{absolute_path: string}> $attachments */
    protected function appendPdfAttachments(Mpdf $pdf, Collection $attachments): void
    {
        foreach ($attachments as $attachment) {
            $pageCount = $pdf->setSourceFile($attachment['absolute_path']);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $template = $pdf->importPage($pageNumber);
                $pdf->AddPage();
                $pdf->useTemplate($template, ['adjustPageSize' => true]);
            }
        }
    }
}
