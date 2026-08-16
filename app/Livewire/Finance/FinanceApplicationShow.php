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
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class FinanceApplicationShow extends Component
{
    use WithFileUploads;

    public const MAX_VEHICLES = 3;

    public FinanceApplication $application;

    public int $wizardStep = 1;

    public array $form = [];

    /** @var list<string> */
    public array $selectedVehicleIds = [];

    public ?int $selectedModelYear = null;

    public ?string $selectedModel = null;

    public ?string $pendingVehicleId = null;

    public array $acceptanceDocs = [];

    public ?string $bookingDate = null;

    public function mount(FinanceApplication $application, FinanceApplicationRepositoryInterface $repo): void
    {
        $this->authorize('view', $application);
        $this->application = $repo->findWithRelations($application->app_id) ?? $application;
        $this->form = $this->application->only([
            'source', 'financial_merchant_id', 'financial_product_id',
            'down_payment', 'total_loan_amount', 'total_truck_price', 'monthly_income', 'customer_comm_notes',
        ]);
        $this->selectedVehicleIds = $this->application->selectedVehicles()
            ->pluck('id')
            ->values()
            ->all();
        $this->bookingDate = $this->application->booking_effective_date?->format('Y-m-d');
    }

    public function updatedSelectedModelYear(): void
    {
        $this->selectedModel = null;
        $this->pendingVehicleId = null;
    }

    public function updatedSelectedModel(): void
    {
        $this->pendingVehicleId = null;
    }

    public function addVehicle(): void
    {
        $this->validate([
            'selectedModelYear' => ['required', 'integer', 'exists:auto_products,model_year'],
            'selectedModel' => ['required', 'string', 'max:120'],
            'pendingVehicleId' => $this->pendingVehicleRules(),
        ]);

        if (in_array($this->pendingVehicleId, $this->selectedVehicleIds, true)) {
            throw ValidationException::withMessages([
                'pendingVehicleId' => __('finance.vehicle_already_added'),
            ]);
        }

        if (count($this->selectedVehicleIds) >= self::MAX_VEHICLES) {
            throw ValidationException::withMessages([
                'pendingVehicleId' => __('finance.vehicle_limit', ['max' => self::MAX_VEHICLES]),
            ]);
        }

        $this->selectedVehicleIds[] = $this->pendingVehicleId;
        $this->pendingVehicleId = null;
    }

    public function removeVehicle(string $vehicleId): void
    {
        $this->selectedVehicleIds = array_values(array_filter(
            $this->selectedVehicleIds,
            fn (string $id) => $id !== $vehicleId,
        ));
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
                'selectedVehicleIds' => ['required', 'array', 'min:1', 'max:'.self::MAX_VEHICLES],
                'selectedVehicleIds.*' => $this->selectedVehicleIdRules(),
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
            'selectedVehicleIds' => ['required', 'array', 'min:1', 'max:'.self::MAX_VEHICLES],
            'selectedVehicleIds.*' => $this->selectedVehicleIdRules(),
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

    /** @return list<mixed> */
    protected function pendingVehicleRules(): array
    {
        return [
            'required',
            'uuid',
            Rule::exists('auto_products', 'id')
                ->where('model_year', $this->selectedModelYear)
                ->where('model', $this->selectedModel)
                ->where('is_active', true),
        ];
    }

    /** @return list<mixed> */
    protected function selectedVehicleIdRules(): array
    {
        return [
            'required',
            'uuid',
            Rule::exists('auto_products', 'id')->where('is_active', true),
            'distinct',
        ];
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
        $this->application = $service->update($this->application, $this->form, $this->selectedVehicleIds);
    }

    public function submitForReview(FinanceApplicationService $service): void
    {
        $this->authorize('update', $this->application);
        $this->validate($this->completeDraftRules());
        $this->application = $service->update($this->application, $this->form, $this->selectedVehicleIds);
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

    /** @return Collection<int, AutoProduct> */
    protected function selectedVehicles(): Collection
    {
        if ($this->selectedVehicleIds === []) {
            return collect();
        }

        $products = AutoProduct::query()
            ->whereIn('id', $this->selectedVehicleIds)
            ->get()
            ->keyBy('id');

        return collect($this->selectedVehicleIds)
            ->map(fn (string $id) => $products->get($id))
            ->filter()
            ->values();
    }

    public function render(FinanceApplicationPdfService $pdfService)
    {
        $activeProducts = AutoProduct::query()->where('is_active', true);

        $modelOptions = (clone $activeProducts)
            ->when(
                $this->selectedModelYear,
                fn ($query) => $query->where('model_year', $this->selectedModelYear),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->whereNotNull('model')
            ->where('model', '!=', '')
            ->orderBy('model')
            ->distinct()
            ->pluck('model');

        $nameOptions = (clone $activeProducts)
            ->when(
                $this->selectedModelYear && $this->selectedModel,
                fn ($query) => $query
                    ->where('model_year', $this->selectedModelYear)
                    ->where('model', $this->selectedModel),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->orderBy('brand')
            ->orderBy('name')
            ->get();

        return view('livewire.finance.finance-application-show', [
            'merchants' => Merchant::query()->where('is_active', true)->get(),
            'modelYears' => (clone $activeProducts)
                ->whereNotNull('model_year')
                ->distinct()
                ->orderByDesc('model_year')
                ->pluck('model_year'),
            'modelOptions' => $modelOptions,
            'nameOptions' => $nameOptions,
            'selectedVehicles' => $this->selectedVehicles(),
            'financialProducts' => FinancialProduct::query()->where('is_active', true)->get(),
            'canDownloadPdf' => $pdfService->canGenerate($this->application),
            'maxVehicles' => self::MAX_VEHICLES,
        ]);
    }
}
