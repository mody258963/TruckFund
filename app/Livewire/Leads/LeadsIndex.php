<?php

namespace App\Livewire\Leads;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Enums\LeadStatus;
use App\Enums\LeadValue;
use App\Models\User;
use App\Services\LeadService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class LeadsIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $statusFilter = null;

    public ?int $valueFilter = null;

    public bool $priorityOnly = false;

    public bool $showCreate = false;

    public string $customer_name = '';

    public string $phone = '';

    public string $email = '';

    public string $car_brand = '';

    public ?float $price = null;

    public function create(LeadService $service): void
    {
        $this->authorize('create', \App\Models\Lead::class);
        $this->validate([
            'customer_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
        ]);
        $service->create([
            'customer_name' => $this->customer_name,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'car_brand' => $this->car_brand ?: null,
            'price' => $this->price,
        ]);
        $this->reset(['customer_name', 'phone', 'email', 'car_brand', 'price', 'showCreate']);
        $this->dispatch('notify', message: __('leads.created'));
    }

    public function render(LeadRepositoryInterface $leads)
    {
        return view('livewire.leads.leads-index', [
            'leads' => $leads->paginate(15, array_filter([
                'search' => $this->search,
                'status' => $this->statusFilter,
                'value' => $this->valueFilter,
                'priority' => $this->priorityOnly ?: null,
            ])),
            'statuses' => LeadStatus::cases(),
            'values' => LeadValue::cases(),
            'agents' => User::query()->where('is_active', true)->get(),
        ]);
    }
}
