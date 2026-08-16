<?php

namespace App\Livewire\Leads;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadValue;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Services\LeadService;
use App\Services\UserHierarchyService;
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

    public ?string $assignedFilter = null;

    public bool $priorityOnly = false;

    public bool $showCreate = false;

    public string $customer_name = '';

    public string $phone = '';

    public string $email = '';

    public string $car_brand = '';

    public ?float $price = null;

    public ?string $freelancer_id = null;

    public ?string $follow_up_on = null;

    /** @var array<int, string> */
    public array $selectedLeads = [];

    public ?string $bulkAssignUserId = null;

    public function create(LeadService $service): void
    {
        $this->authorize('create', Lead::class);
        $this->validate([
            'customer_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'follow_up_on' => 'nullable|date_format:Y-m-d',
        ]);
        $source = auth()->user()->isAdmin() ? LeadSource::AdminDashboard : LeadSource::SalesInput;
        $service->create([
            'customer_name' => $this->customer_name,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'car_brand' => $this->car_brand ?: null,
            'price' => $this->price,
            'freelancer_id' => $this->freelancer_id,
            'follow_up_on' => $this->follow_up_on,
            'assigned_user_id' => auth()->user()->isSales() ? auth()->id() : null,
        ], auth()->user(), $source);
        $this->reset([
            'customer_name',
            'phone',
            'email',
            'car_brand',
            'price',
            'freelancer_id',
            'follow_up_on',
            'showCreate',
        ]);
        $this->dispatch('notify', message: __('leads.created'));
    }

    public function saveFollowUp(string $leadId, ?string $date, LeadService $service): void
    {
        $lead = Lead::query()->findOrFail($leadId);
        $this->authorize('update', $lead);

        $validated = validator(
            ['date' => $date ?: null],
            ['date' => 'nullable|date_format:Y-m-d'],
        )->validate();

        $service->update($lead, ['follow_up_on' => $validated['date']]);
        $this->dispatch('notify', message: __('leads.follow_up_saved'));
    }

    public function toggleSelectAllOnPage(array $pageLeadIds): void
    {
        $pageLeadIds = array_values($pageLeadIds);
        $allSelected = $pageLeadIds !== []
            && count(array_intersect($pageLeadIds, $this->selectedLeads)) === count($pageLeadIds);

        if ($allSelected) {
            $this->selectedLeads = array_values(array_diff($this->selectedLeads, $pageLeadIds));
        } else {
            $this->selectedLeads = array_values(array_unique([...$this->selectedLeads, ...$pageLeadIds]));
        }
    }

    public function clearSelection(): void
    {
        $this->selectedLeads = [];
        $this->bulkAssignUserId = null;
    }

    public function bulkAssign(LeadService $service): void
    {
        $this->authorize('bulkAssign', Lead::class);
        $this->validate([
            'selectedLeads' => 'required|array|min:1',
            'selectedLeads.*' => 'uuid|exists:leads,lead_id',
            'bulkAssignUserId' => 'required|uuid|exists:users,user_id',
        ]);

        $count = $service->bulkAssign($this->selectedLeads, $this->bulkAssignUserId, auth()->user());
        $this->clearSelection();
        $this->dispatch('notify', message: __('leads.bulk_assigned', ['count' => $count]));
    }

    public function render(LeadRepositoryInterface $leads, UserHierarchyService $hierarchy)
    {
        $viewer = auth()->user();
        $canBulkAssign = $viewer->can('bulkAssign', Lead::class);

        $salesAgents = $canBulkAssign
            ? $hierarchy->assignableUsersQuery($viewer)
                ->where('role', UserRole::SalesAgent->value)
                ->orderBy('full_name')
                ->get()
            : collect();

        return view('livewire.leads.leads-index', [
            'leads' => $leads->paginate(15, array_filter([
                'search' => $this->search,
                'status' => $this->statusFilter,
                'value' => $this->valueFilter,
                'assigned_user_id' => $this->assignedFilter,
                'priority' => $this->priorityOnly ?: null,
            ]), $viewer),
            'statuses' => LeadStatus::cases(),
            'values' => LeadValue::cases(),
            'agents' => $hierarchy->assignableUsersQuery($viewer)->orderBy('full_name')->get(),
            'salesAgents' => $salesAgents,
            'freelancers' => \App\Models\Freelancer::query()->orderBy('full_name')->limit(200)->get(),
            'canBulkAssign' => $canBulkAssign,
        ]);
    }
}
