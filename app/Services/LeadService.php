<?php

namespace App\Services;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(
        protected LeadRepositoryInterface $leads,
        protected LeadScoringService $scoring,
        protected CommunicationLogService $communicationLogs,
        protected CustomerOnboardingService $onboarding,
    ) {}

    public function create(array $data): Lead
    {
        $score = $this->scoring->scoreFromInputs($data);
        $value = $this->scoring->valueFromScore($score);

        return $this->leads->create([
            ...$data,
            'lead_number' => $this->leads->generateLeadNumber(),
            'status' => LeadStatus::New,
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

    public function assign(Lead $lead, string $userId): Lead
    {
        return $this->leads->update($lead, ['assigned_user_id' => $userId]);
    }

    public function updateStatus(Lead $lead, LeadStatus $status): Lead
    {
        return $this->leads->update($lead, ['status' => $status]);
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
