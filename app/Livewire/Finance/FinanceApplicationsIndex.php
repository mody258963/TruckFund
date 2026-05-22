<?php

namespace App\Livewire\Finance;

use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Enums\ApplicationStatus;
use App\Models\Customer;
use App\Services\FinanceApplicationService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class FinanceApplicationsIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreate = false;

    public string $customer_id = '';

    public function create(FinanceApplicationService $service): void
    {
        $this->authorize('create', \App\Models\FinanceApplication::class);
        $this->validate([
            'customer_id' => 'required|uuid|exists:customers,customer_id',
        ]);
        $app = $service->createDraft([
            'customer_id' => $this->customer_id,
            'user_id' => auth()->id(),
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);
        $this->redirect(route('finance.show', $app), navigate: true);
    }

    public function render(FinanceApplicationRepositoryInterface $apps)
    {
        return view('livewire.finance.finance-applications-index', [
            'applications' => $apps->paginate(15, ['search' => $this->search]),
            'customers' => Customer::query()->orderBy('display_name')->get(),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }
}
