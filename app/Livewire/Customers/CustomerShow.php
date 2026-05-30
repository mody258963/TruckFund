<?php

namespace App\Livewire\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
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

    public function render(ImageStorageService $images)
    {
        return view('livewire.customers.customer-show', [
            'images' => $images,
        ]);
    }
}
