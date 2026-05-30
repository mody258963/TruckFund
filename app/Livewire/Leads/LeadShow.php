<?php

namespace App\Livewire\Leads;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Enums\CommType;
use App\Enums\LeadStatus;
use App\Enums\TransferRequestStatus;
use App\Models\Lead;
use App\Models\LeadTransferRequest;
use App\Services\CommunicationLogService;
use App\Services\LeadService;
use App\Services\LeadTransferService;
use App\Services\UserHierarchyService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LeadShow extends Component
{
    public Lead $lead;

    public ?string $assigned_user_id = null;

    public int $status;

    public string $commContent = '';

    public ?string $transfer_to_user_id = null;

    public string $transfer_reason = '';

    public function mount(Lead $lead, LeadRepositoryInterface $repo): void
    {
        $this->authorize('view', $lead);
        $this->lead = $repo->findWithRelations($lead->lead_id) ?? $lead;
        $this->assigned_user_id = $this->lead->assigned_user_id;
        $this->status = $this->lead->status->value;
    }

    public function saveAssignment(LeadService $service): void
    {
        $this->authorize('assign', $this->lead);
        if ($this->assigned_user_id) {
            $service->assign($this->lead, $this->assigned_user_id, auth()->user());
        }
        $this->lead->refresh();
    }

    public function saveStatus(LeadService $service): void
    {
        $this->authorize('update', $this->lead);
        $service->updateStatus($this->lead, LeadStatus::from($this->status));
        $this->lead->refresh();
    }

    public function requestTransfer(LeadTransferService $transfers): void
    {
        $this->authorize('requestTransfer', $this->lead);
        $this->validate([
            'transfer_to_user_id' => 'required|uuid|exists:users,user_id',
            'transfer_reason' => 'nullable|string|max:500',
        ]);
        $transfers->request($this->lead, auth()->user(), $this->transfer_to_user_id, $this->transfer_reason);
        $this->reset(['transfer_to_user_id', 'transfer_reason']);
        $this->lead->load('transferRequests.toUser');
        $this->dispatch('notify', message: __('crm.transfer.pending'));
    }

    public function approveTransfer(string $transferId, LeadTransferService $transfers): void
    {
        $transfer = LeadTransferRequest::query()->findOrFail($transferId);
        $transfers->approve($transfer, auth()->user());
        $this->lead->refresh();
        $this->assigned_user_id = $this->lead->assigned_user_id;
        $this->lead->load('transferRequests.toUser');
    }

    public function rejectTransfer(string $transferId, LeadTransferService $transfers): void
    {
        $transfer = LeadTransferRequest::query()->findOrFail($transferId);
        $transfers->reject($transfer, auth()->user());
        $this->lead->load('transferRequests.toUser');
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

    public function render(UserHierarchyService $hierarchy)
    {
        $user = auth()->user();
        $pendingTransfer = $this->lead->transferRequests
            ->where('status', TransferRequestStatus::Pending)
            ->first();

        $transferTargets = collect();
        if ($user->isSales() && $user->reports_to_user_id) {
            $manager = \App\Models\User::query()->find($user->reports_to_user_id);
            if ($manager) {
                $transferTargets = $hierarchy->assignableUsersQuery($manager)
                    ->where('user_id', '!=', $user->user_id)
                    ->orderBy('full_name')
                    ->get();
            }
        }

        return view('livewire.leads.lead-show', [
            'agents' => $hierarchy->assignableUsersQuery($user)->orderBy('full_name')->get(),
            'statuses' => LeadStatus::cases(),
            'pendingTransfer' => $pendingTransfer,
            'transferTargets' => $transferTargets,
            'canAssign' => $user->can('assign', $this->lead),
            'canRequestTransfer' => $user->can('requestTransfer', $this->lead),
        ]);
    }
}
