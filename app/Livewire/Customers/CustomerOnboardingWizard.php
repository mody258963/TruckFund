<?php

namespace App\Livewire\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Enums\DocType;
use App\Models\Customer;
use App\Services\CustomerOnboardingService;
use App\Services\ImageStorageService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class CustomerOnboardingWizard extends Component
{
    use WithFileUploads;

    public Customer $customer;

    public int $step = 1;

    public int $totalSteps = CustomerOnboardingService::TOTAL_STEPS;

    public array $form = [];

    public $idFront;

    public $idBack;

    public array $incomeProofFiles = [];

    public array $commercialRegFiles = [];

    public array $extraDocFiles = [];

    public int $extraDocType = DocType::Other->value;

    public function mount(Customer $customer, CustomerRepositoryInterface $repo): void
    {
        $this->authorize('update', $customer);
        $this->customer = $repo->findWithRelations($customer->customer_id) ?? $customer;
        $this->step = CustomerOnboardingService::normalizeStep((int) $this->customer->onboarding_step);
        $fin = $this->customer->financialData;

        $this->form = [
            'display_name' => $this->customer->display_name,
            'mobile_number' => $this->customer->mobile_number,
            'email' => $this->customer->email,
            'city' => $this->customer->city,
            'area' => $this->customer->area,
            'address' => $this->customer->address,
            'id_number' => $this->customer->identification?->id_number ?? '',
            'name_en' => $this->customer->identification?->name_en ?? '',
            'name_ar' => $this->customer->identification?->name_ar ?? '',
            'references' => $this->customer->references->map->only(['full_name', 'mobile', 'relation', 'same_address'])->toArray()
                ?: [['full_name' => '', 'mobile' => '', 'relation' => null, 'same_address' => false]],
            'has_income_proof' => $fin?->has_income_proof ?? false,
            'annual_sales_1yr' => $fin?->annual_sales_1yr,
            'annual_sales_2yr' => $fin?->annual_sales_2yr,
            'org_name' => $fin?->org_name ?? '',
            'commercial_reg_type' => $fin?->commercial_reg_type,
            'commercial_reg_num' => $fin?->commercial_reg_num ?? '',
            'reg_start_date' => $fin?->reg_start_date?->format('Y-m-d'),
            'reg_expiry_date' => $fin?->reg_expiry_date?->format('Y-m-d'),
            'paid_in_capital' => $fin?->paid_in_capital,
            'org_city' => $fin?->org_city ?? '',
            'org_address' => $fin?->org_address ?? '',
        ];
    }

    public function saveStep(CustomerOnboardingService $onboarding): void
    {
        if ($this->step === 2) {
            $maxKb = config('truckfund.image_max_kb', 2048);
            $rules = [
                'form.id_number' => [
                    'required',
                    'digits:14',
                    Rule::unique('identifications', 'id_number')
                        ->ignore($this->customer->customer_id, 'customer_id'),
                ],
            ];
            if ($this->idFront) {
                $rules['idFront'] = "image|max:{$maxKb}";
            }
            if ($this->idBack) {
                $rules['idBack'] = "image|max:{$maxKb}";
            }
            $this->validate($rules, [
                'form.id_number.digits' => __('customers.id_number_invalid'),
                'form.id_number.unique' => __('customers.id_number_taken'),
            ]);
            if ($this->idFront) {
                $onboarding->storeIdImage($this->customer, $this->idFront, 'front');
            }
            if ($this->idBack) {
                $onboarding->storeIdImage($this->customer, $this->idBack, 'back');
            }
        }

        if ($this->step === 5) {
            $maxKb = config('truckfund.document_max_kb', 10240);
            $this->validate([
                'form.annual_sales_1yr' => 'nullable|numeric|min:0',
                'form.annual_sales_2yr' => 'nullable|numeric|min:0',
                'form.commercial_reg_type' => 'nullable|string|max:100',
                'form.commercial_reg_num' => 'nullable|string|max:100',
                'form.paid_in_capital' => 'nullable|numeric|min:0',
                'incomeProofFiles' => 'nullable|array|max:20',
                'incomeProofFiles.*' => "file|max:{$maxKb}|mimes:jpg,jpeg,png,webp,pdf",
                'commercialRegFiles' => 'nullable|array|max:20',
                'commercialRegFiles.*' => "file|max:{$maxKb}|mimes:jpg,jpeg,png,webp,pdf",
            ]);
        }

        if ($this->step === 6 && $this->extraDocFiles !== []) {
            $this->uploadExtraDocument($onboarding);
        }

        $wasFinalStep = $this->step === CustomerOnboardingService::TOTAL_STEPS;

        $this->customer = $onboarding->saveStep(
            $this->customer,
            $this->step,
            $this->form,
            $this->step === 5 ? $this->incomeProofFiles : [],
            $this->step === 5 ? $this->commercialRegFiles : [],
        );

        if ($wasFinalStep) {
            // Finishing the wizard used to leave the user on the same screen with
            // no feedback, which looked like the button was dead.
            $this->reset('extraDocFiles');
            $this->redirect(route('customers.show', $this->customer), navigate: true);

            return;
        }

        $this->step++;

        if ($this->step === 6) {
            $this->reset(['incomeProofFiles', 'commercialRegFiles']);
        }

        $this->customer->refresh();
        $this->customer->load('documents');
    }

    public function previous(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function uploadExtraDocument(CustomerOnboardingService $onboarding): void
    {
        $maxKb = config('truckfund.document_max_kb', 10240);
        $allowedTypes = array_map(fn (DocType $t) => $t->value, CustomerOnboardingService::EXTRA_DOC_TYPES);

        $this->validate([
            'extraDocFiles' => 'required|array|min:1|max:20',
            'extraDocFiles.*' => "file|max:{$maxKb}|mimes:jpg,jpeg,png,webp,pdf",
            'extraDocType' => ['required', 'integer', Rule::in($allowedTypes)],
        ]);

        $type = DocType::from($this->extraDocType);

        foreach ($this->extraDocFiles as $file) {
            $onboarding->storeUpload($this->customer, $file, $type);
        }

        $this->reset('extraDocFiles');
        $this->customer->refresh();
        $this->customer->load('documents');
    }

    public function removeDocument(string $docId, CustomerOnboardingService $onboarding): void
    {
        $onboarding->deleteDocument($this->customer, $docId);
        $this->customer->refresh();
        $this->customer->load('documents');
    }

    public function render(ImageStorageService $images)
    {
        $uploadedDocs = $this->customer->documents
            ->whereIn('doc_type', [DocType::IncomeProof, DocType::CommercialReg])
            ->groupBy(fn ($d) => $d->doc_type->name);

        $extraDocuments = $this->customer->documents
            ->filter(fn ($d) => in_array($d->doc_type, CustomerOnboardingService::EXTRA_DOC_TYPES, true))
            ->values();

        return view('livewire.customers.customer-onboarding-wizard', [
            'uploadedDocs' => $uploadedDocs,
            'extraDocuments' => $extraDocuments,
            'extraDocTypes' => CustomerOnboardingService::EXTRA_DOC_TYPES,
            'images' => $images,
        ]);
    }
}
