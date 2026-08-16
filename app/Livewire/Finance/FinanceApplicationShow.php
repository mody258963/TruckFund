<?php

namespace App\Livewire\Finance;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Enums\ApplicationStatus;
use App\Enums\DocType;
use App\Models\AutoProduct;
use App\Models\FinanceApplication;
use App\Models\FinancialProduct;
use App\Models\Merchant;
use App\Services\FinanceApplicationPdfService;
use App\Services\FinanceApplicationService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class FinanceApplicationShow extends Component
{
    use WithFileUploads;

    public FinanceApplication $application;

    public int $wizardStep = 1;

    public array $form = [];

    public array $acceptanceDocs = [];

    public ?string $bookingDate = null;

    public function mount(FinanceApplication $application, FinanceApplicationRepositoryInterface $repo): void
    {
        $this->authorize('view', $application);
        $this->application = $repo->findWithRelations($application->app_id) ?? $application;
        $this->form = $this->application->only([
            'source', 'financial_merchant_id', 'auto_product_id', 'financial_product_id',
            'down_payment', 'total_loan_amount', 'total_truck_price', 'monthly_income', 'customer_comm_notes',
        ]);
        $this->bookingDate = $this->application->booking_effective_date?->format('Y-m-d');
    }

    public function nextDraftStep(): void
    {
        $this->validate($this->draftStepRules());
        $this->wizardStep = min(3, $this->wizardStep + 1);
    }

    public function previousDraftStep(): void
    {
        $this->wizardStep = max(1, $this->wizardStep - 1);
    }

    /** @return array<string, mixed> */
    protected function draftStepRules(): array
    {
        return match ($this->wizardStep) {
            1 => [
                'form.financial_merchant_id' => $this->merchantRules(),
                'form.auto_product_id' => $this->autoProductRules(),
            ],
            2 => [
                'form.financial_product_id' => $this->financialProductRules(),
                'form.total_truck_price' => 'required|numeric|min:0',
                'form.down_payment' => 'required|numeric|min:0',
                'form.total_loan_amount' => 'required|numeric|min:0',
                'form.monthly_income' => 'nullable|numeric|min:0',
            ],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    protected function completeDraftRules(): array
    {
        return [
            'form.financial_merchant_id' => $this->merchantRules(),
            'form.auto_product_id' => $this->autoProductRules(),
            'form.financial_product_id' => $this->financialProductRules(),
            'form.total_truck_price' => 'required|numeric|min:0',
            'form.down_payment' => 'required|numeric|min:0',
            'form.total_loan_amount' => 'required|numeric|min:0',
            'form.monthly_income' => 'nullable|numeric|min:0',
        ];
    }

    /** @return list<string> */
    protected function merchantRules(): array
    {
        return ['required', 'uuid', 'exists:merchants,merchant_id'];
    }

    /** @return list<string> */
    protected function autoProductRules(): array
    {
        return ['required', 'uuid', 'exists:auto_products,id'];
    }

    /** @return list<string> */
    protected function financialProductRules(): array
    {
        return ['required', 'uuid', 'exists:financial_products,product_id'];
    }

    public function saveDraft(FinanceApplicationService $service): void
    {
        $this->authorize('update', $this->application);
        if ($this->application->status === ApplicationStatus::Draft) {
            $this->validate($this->completeDraftRules());
        }
        $this->application = $service->update($this->application, $this->form);
    }

    public function submitForReview(FinanceApplicationService $service): void
    {
        $this->authorize('update', $this->application);
        $this->validate($this->completeDraftRules());
        $this->application = $service->update($this->application, $this->form);
        $this->application = $service->submitForReview($this->application);
    }

    public function decide(string $decision, FinanceApplicationService $service): void
    {
        $this->authorize('decide', $this->application);
        $status = ApplicationStatus::from(match ($decision) {
            'accept' => ApplicationStatus::Accepted->value,
            'reject' => ApplicationStatus::Rejected->value,
            'cancel' => ApplicationStatus::Cancelled->value,
        });
        $this->application = $service->decide($this->application, $status);
    }

    public function confirmBooking(FinanceApplicationService $service): void
    {
        $this->validate(['bookingDate' => 'required|date']);
        $this->application = $service->confirmBooking($this->application, $this->bookingDate);
    }

    public function uploadDoc(FinanceApplicationService $service): void
    {
        $maxKb = config('truckfund.document_max_kb', 10240);
        $this->validate([
            'acceptanceDocs' => 'required|array|min:1|max:20',
            'acceptanceDocs.*' => "file|max:{$maxKb}|mimes:jpg,jpeg,png,webp,pdf",
        ]);
        $service->uploadDocuments($this->application, $this->acceptanceDocs, DocType::AcceptancePaper, auth()->id());
        $this->application->refresh();
        $this->application->load('applicationDocuments');
        $this->reset('acceptanceDocs');
    }

    public function complete(FinanceApplicationService $service): void
    {
        $this->application = $service->complete($this->application);
    }

    public function render(FinanceApplicationPdfService $pdfService)
    {
        return view('livewire.finance.finance-application-show', [
            'merchants' => Merchant::query()->where('is_active', true)->get(),
            'autoProducts' => AutoProduct::query()->where('is_active', true)->get(),
            'financialProducts' => FinancialProduct::query()->where('is_active', true)->get(),
            'canDownloadPdf' => $pdfService->canGenerate($this->application),
        ]);
    }
}
