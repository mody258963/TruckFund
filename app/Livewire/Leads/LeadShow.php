<?php

namespace App\Livewire\Leads;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Enums\CommType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Services\CommunicationLogService;
use App\Services\LeadService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LeadShow extends Component
{
    public Lead $lead;

    public ?string $assigned_user_id = null;

    public int $status;

    public string $commContent = '';

    public function mount(Lead $lead, LeadRepositoryInterface $repo): void
    {
        $this->authorize('view', $lead);
        $this->lead = $repo->findWithRelations($lead->lead_id) ?? $lead;
        $this->assigned_user_id = $this->lead->assigned_user_id;
        $this->status = $this->lead->status->value;
    }

    public function saveAssignment(LeadService $service): void
    {
        $this->authorize('update', $this->lead);
        if ($this->assigned_user_id) {
            $service->assign($this->lead, $this->assigned_user_id);
        }
        $this->lead->refresh();
    }

    public function saveStatus(LeadService $service): void
    {
        $this->authorize('update', $this->lead);
        $service->updateStatus($this->lead, LeadStatus::from($this->status));
        $this->lead->refresh();
    }

    public function addNote(CommunicationLogService $logs): void
    {
        $this->validate(['commContent' => 'required|string|min:2']);
        $logs->logForLead($this->lead->lead_id, CommType::Note, $this->commContent);
        $this->commContent = '';
        $this->lead->load('communicationLogs.user');
    }

    public function convert(LeadService $service): void
    {
        $this->authorize('update', $this->lead);
        $customer = $service->convertToCustomer($this->lead);
        $this->redirect(route('customers.onboarding', $customer), navigate: true);
    }

    public function render()
    {
        return view('livewire.leads.lead-show', [
            'agents' => User::query()->where('is_active', true)->get(),
            'statuses' => LeadStatus::cases(),
        ]);
    }
}
