<?php

namespace App\Services;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadService
{
    public function __construct(
        protected LeadRepositoryInterface $leads,
        protected LeadScoringService $scoring,
        protected CommunicationLogService $communicationLogs,
        protected CustomerOnboardingService $onboarding,
        protected UserHierarchyService $hierarchy,
    ) {}

    public function create(array $data, ?User $creator = null, LeadSource $source = LeadSource::SalesInput): Lead
    {
        $score = $this->scoring->scoreFromInputs($data);
        $value = $this->scoring->valueFromScore($score);

        $creator ??= auth()->user();

        return $this->leads->create([
            ...$data,
            'lead_number' => $this->leads->generateLeadNumber(),
            'status' => LeadStatus::New,
            'source' => $data['source'] ?? $source,
            'created_by_user_id' => $creator?->user_id,
            'ai_score' => $score,
            'value' => $value,
            'is_priority' => $this->scoring->isPriority($score),
        ]);
    }

    public function update(Lead $lead, array $data): Lead
    {
        if (isset($data['price']) || isset($data['down_payment_pct'])) {
            $merged = array_merge($lead->toArray(), $data);
            $score = $this->scoring->scoreFromInputs($merged);
            $data['ai_score'] = $score;
            $data['value'] = $this->scoring->valueFromScore($score);
            $data['is_priority'] = $this->scoring->isPriority($score);
        }

        return $this->leads->update($lead, $data);
    }

    public function assign(Lead $lead, string $userId, ?User $assigner = null): Lead
    {
        $assigner ??= auth()->user();
        $assignee = User::query()->findOrFail($userId);

        if ($assigner && ! $this->hierarchy->canAssignLeadTo($assigner, $assignee)) {
            abort(403, __('crm.leads.cannot_assign'));
        }

        return $this->leads->update($lead, ['assigned_user_id' => $userId]);
    }

    /**
     * @param  array<int, string>  $leadIds
     */
    public function bulkAssign(array $leadIds, string $assigneeUserId, User $assigner): int
    {
        $assignee = User::query()->findOrFail($assigneeUserId);

        if (! $this->hierarchy->canAssignLeadTo($assigner, $assignee)) {
            throw ValidationException::withMessages([
                'bulkAssignUserId' => __('crm.leads.cannot_assign'),
            ]);
        }

        $count = 0;

        DB::transaction(function () use ($leadIds, $assigneeUserId, $assigner, &$count) {
            foreach (array_unique($leadIds) as $leadId) {
                $lead = Lead::query()->find($leadId);
                if (! $lead) {
                    continue;
                }

                if (! $assigner->can('assign', $lead)) {
                    continue;
                }

                $this->leads->update($lead, ['assigned_user_id' => $assigneeUserId]);
                $count++;
            }
        });

        if ($count === 0) {
            throw ValidationException::withMessages([
                'selectedLeads' => __('leads.bulk_none_assigned'),
            ]);
        }

        return $count;
    }

    public function updateStatus(Lead $lead, LeadStatus $status): Lead
    {
        return $this->leads->update($lead, ['status' => $status]);
    }

    public function delete(Lead $lead): void
    {
        $this->leads->delete($lead);
    }

    public function convertToCustomer(Lead $lead): Customer
    {
        return DB::transaction(function () use ($lead) {
            $customer = $this->onboarding->createFromLead($lead);
            $this->leads->update($lead, [
                'customer_id' => $customer->customer_id,
                'status' => LeadStatus::ResolvedProfileCreated,
            ]);

            return $customer;
        });
    }
}
