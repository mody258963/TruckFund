<?php

namespace App\Livewire\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
use App\Models\FinanceApplication;
use App\Services\FinanceApplicationService;
use App\Services\ImageStorageService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CustomerShow extends Component
{
    public Customer $customer;

    public function mount(Customer $customer, CustomerRepositoryInterface $repo): void
    {
        $this->customer = $repo->findWithRelations($customer->customer_id)
            ?? abort(404);
    }

    public function createFinanceApplication(FinanceApplicationService $service): void
    {
        $this->authorize('create', FinanceApplication::class);

        $app = $service->createDraft([
            'customer_id' => $this->customer->customer_id,
            'user_id' => auth()->id(),
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);

        $this->redirect(route('finance.show', $app), navigate: true);
    }

    public function render(ImageStorageService $images)
    {
        return view('livewire.customers.customer-show', [
            'images' => $images,
        ]);
    }
}
