<?php

namespace App\Services;

use App\Enums\TransferRequestStatus;
use App\Models\Lead;
use App\Models\LeadTransferRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadTransferService
{
    public function __construct(
        protected LeadService $leads,
        protected UserHierarchyService $hierarchy,
        protected CommunicationLogService $communicationLogs,
    ) {}

    public function request(Lead $lead, User $sales, string $toUserId, ?string $reason = null): LeadTransferRequest
    {
        if (! $sales->role->canRequestLeadTransfer()) {
            throw ValidationException::withMessages(['transfer' => __('crm.transfer.sales_only')]);
        }

        if ($lead->assigned_user_id !== $sales->user_id) {
            throw ValidationException::withMessages(['transfer' => __('crm.transfer.not_assigned')]);
        }

        $toUser = User::query()->findOrFail($toUserId);
        $manager = $sales->reports_to_user_id
            ? User::query()->find($sales->reports_to_user_id)
            : null;

        if (! $manager || ! $this->hierarchy->canAssignLeadTo($manager, $toUser)) {
            throw ValidationException::withMessages(['transfer' => __('crm.transfer.invalid_target')]);
        }

        $pending = LeadTransferRequest::query()
            ->where('lead_id', $lead->lead_id)
            ->where('status', TransferRequestStatus::Pending)
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages(['transfer' => __('crm.transfer.pending_exists')]);
        }

        return LeadTransferRequest::query()->create([
            'lead_id' => $lead->lead_id,
            'requested_by_user_id' => $sales->user_id,
            'from_user_id' => $lead->assigned_user_id,
            'to_user_id' => $toUserId,
            'status' => TransferRequestStatus::Pending,
            'reason' => $reason,
        ]);
    }

    public function approve(LeadTransferRequest $transfer, User $reviewer, ?string $note = null): Lead
    {
        $this->assertCanReview($transfer, $reviewer);

        return DB::transaction(function () use ($transfer, $reviewer, $note) {
            $lead = $this->leads->assign($transfer->lead, $transfer->to_user_id);

            $transfer->update([
                'status' => TransferRequestStatus::Approved,
                'reviewed_by_user_id' => $reviewer->user_id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);

            $this->communicationLogs->logForLead(
                $lead->lead_id,
                \App\Enums\CommType::Note,
                __('crm.transfer.approved_log', ['to' => $transfer->toUser?->full_name ?? $transfer->to_user_id])
            );

            return $lead;
        });
    }

    public function reject(LeadTransferRequest $transfer, User $reviewer, ?string $note = null): LeadTransferRequest
    {
        $this->assertCanReview($transfer, $reviewer);

        $transfer->update([
            'status' => TransferRequestStatus::Rejected,
            'reviewed_by_user_id' => $reviewer->user_id,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);

        return $transfer;
    }

    protected function assertCanReview(LeadTransferRequest $transfer, User $reviewer): void
    {
        if ($transfer->status !== TransferRequestStatus::Pending) {
            throw ValidationException::withMessages(['transfer' => __('crm.transfer.not_pending')]);
        }

        if ($this->hierarchy->isAdmin($reviewer)) {
            return;
        }

        $requester = $transfer->requestedBy;
        if (! $requester || ! $this->hierarchy->canViewUser($reviewer, $requester)) {
            throw ValidationException::withMessages(['transfer' => __('crm.transfer.cannot_review')]);
        }

        if (! $reviewer->role->canAssignLeads()) {
            throw ValidationException::withMessages(['transfer' => __('crm.transfer.cannot_review')]);
        }
    }
}
