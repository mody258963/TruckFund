<?php

namespace App\Livewire\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class CustomersIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function render(CustomerRepositoryInterface $customers)
    {
        return view('livewire.customers.customers-index', [
            'customers' => $customers->paginate(15, ['search' => $this->search]),
        ]);
    }
}
