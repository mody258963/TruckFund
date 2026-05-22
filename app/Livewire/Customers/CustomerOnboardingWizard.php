<?php

namespace App\Livewire\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
use App\Services\CustomerOnboardingService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class CustomerOnboardingWizard extends Component
{
    use WithFileUploads;

    public Customer $customer;

    public int $step = 1;

    public array $form = [];

    public $idFront;

    public $idBack;

    public function mount(Customer $customer, CustomerRepositoryInterface $repo): void
    {
        $this->customer = $repo->findWithRelations($customer->customer_id) ?? $customer;
        $this->step = max(1, (int) $this->customer->onboarding_step);
        $this->form = [
            'display_name' => $this->customer->display_name,
            'mobile_number' => $this->customer->mobile_number,
            'email' => $this->customer->email,
            'source' => $this->customer->source,
            'city' => $this->customer->city,
            'area' => $this->customer->area,
            'address' => $this->customer->address,
            'id_number' => $this->customer->identification?->id_number ?? '',
            'name_en' => $this->customer->identification?->name_en ?? '',
            'name_ar' => $this->customer->identification?->name_ar ?? '',
            'references' => $this->customer->references->map->only(['full_name', 'mobile', 'relation', 'same_address'])->toArray() ?: [['full_name' => '', 'mobile' => '', 'relation' => null, 'same_address' => false]],
            'has_income_proof' => $this->customer->financialData?->has_income_proof ?? false,
            'org_name' => $this->customer->financialData?->org_name ?? '',
        ];
    }

    public function saveStep(CustomerOnboardingService $onboarding): void
    {
        if ($this->step === 2) {
            if ($this->idFront) {
                $onboarding->storeIdImage($this->customer, $this->idFront, 'front');
            }
            if ($this->idBack) {
                $onboarding->storeIdImage($this->customer, $this->idBack, 'back');
            }
        }
        $this->customer = $onboarding->saveStep($this->customer, $this->step, $this->form);
        if ($this->step < CustomerOnboardingService::TOTAL_STEPS) {
            $this->step++;
        }
        $this->customer->refresh();
    }

    public function previous(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function render()
    {
        return view('livewire.customers.customer-onboarding-wizard');
    }
}
