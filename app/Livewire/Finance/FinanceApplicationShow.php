<?php

namespace App\Livewire\Finance;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Enums\ApplicationStatus;
use App\Enums\DocType;
use App\Models\AutoProduct;
use App\Models\FinanceApplication;
use App\Models\FinancialProduct;
use App\Models\Merchant;
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

    public $acceptanceDoc;

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
                'form.financial_merchant_id' => 'required|uuid',
                'form.auto_product_id' => 'required|uuid',
            ],
            2 => [
                'form.financial_product_id' => 'required|uuid',
                'form.total_truck_price' => 'required|numeric|min:0',
                'form.down_payment' => 'required|numeric|min:0',
                'form.total_loan_amount' => 'required|numeric|min:0',
                'form.monthly_income' => 'nullable|numeric|min:0',
            ],
            default => [],
        };
    }

    public function saveDraft(FinanceApplicationService $service): void
    {
        $this->authorize('update', $this->application);
        if ($this->application->status === ApplicationStatus::Draft) {
            $this->validate([
                'form.financial_merchant_id' => 'required|uuid',
                'form.auto_product_id' => 'required|uuid',
                'form.financial_product_id' => 'required|uuid',
                'form.total_truck_price' => 'required|numeric|min:0',
                'form.down_payment' => 'required|numeric|min:0',
                'form.total_loan_amount' => 'required|numeric|min:0',
            ]);
        }
        $this->application = $service->update($this->application, $this->form);
    }

    public function submitForReview(FinanceApplicationService $service): void
    {
        $this->authorize('update', $this->application);
        $this->validate([
            'form.financial_merchant_id' => 'required|uuid',
            'form.auto_product_id' => 'required|uuid',
            'form.financial_product_id' => 'required|uuid',
            'form.total_truck_price' => 'required|numeric|min:0',
            'form.down_payment' => 'required|numeric|min:0',
            'form.total_loan_amount' => 'required|numeric|min:0',
        ]);
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
        $maxKb = config('truckfund.image_max_kb', 2048);
        $this->validate(['acceptanceDoc' => "required|file|max:{$maxKb}|mimes:jpg,jpeg,png,webp,pdf"]);
        $service->uploadDocument($this->application, $this->acceptanceDoc, DocType::AcceptancePaper, auth()->id());
        $this->application->refresh();
        $this->acceptanceDoc = null;
    }

    public function complete(FinanceApplicationService $service): void
    {
        $this->application = $service->complete($this->application);
    }

    public function render()
    {
        return view('livewire.finance.finance-application-show', [
            'merchants' => Merchant::query()->where('is_active', true)->get(),
            'autoProducts' => AutoProduct::query()->where('is_active', true)->get(),
            'financialProducts' => FinancialProduct::query()->where('is_active', true)->get(),
        ]);
    }
}
