<?php

namespace App\Http\Controllers;

use App\Models\FinanceApplication;
use App\Services\FinanceApplicationPdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FinanceApplicationPdfController extends Controller
{
    public function __invoke(
        FinanceApplication $application,
        FinanceApplicationPdfService $pdfService,
    ): Response {
        Gate::authorize('view', $application);

        $application = $pdfService->loadForPdf($application->app_id);

        if (! $pdfService->canGenerate($application)) {
            abort(422, __('finance.pdf_incomplete'));
        }

        return $pdfService->download($application);
    }
}
