<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Enums\DocType;
use App\Models\AgriculturalLand;
use App\Models\Customer;
use App\Models\Document;
use App\Models\FinancialData;
use App\Models\Identification;
use App\Models\Lead;
use App\Models\Reference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CustomerOnboardingService
{
    public const TOTAL_STEPS = 8;

    public function __construct(protected CustomerRepositoryInterface $customers) {}

    public function createFromLead(Lead $lead): Customer
    {
        return $this->customers->create([
            'lead_id' => $lead->lead_id,
            'display_name' => $lead->customer_name ?? 'Customer',
            'mobile_number' => $lead->phone ?? '',
            'email' => $lead->email,
            'onboarding_step' => 1,
        ]);
    }

    public function saveStep(Customer $customer, int $step, array $data): Customer
    {
        match ($step) {
            1 => $this->customers->update($customer, collect($data)->only([
                'display_name', 'mobile_number', 'email',
            ])->all()),
            2 => $this->saveIdentification($customer, $data),
            3 => $this->customers->update($customer, ['source' => $data['source'] ?? null]),
            4 => $this->customers->update($customer, collect($data)->only([
                'nationality', 'date_of_birth', 'gender', 'marital_status',
                'job_status', 'occupation', 'organization_name',
            ])->all()),
            5 => $this->customers->update($customer, collect($data)->only([
                'city', 'area', 'address',
            ])->all()),
            6 => $this->saveReferences($customer, $data['references'] ?? []),
            7 => $this->saveFinancialData($customer, $data),
            8 => $this->saveExtraDocuments($customer, $data),
            default => null,
        };

        $nextStep = min($step + 1, self::TOTAL_STEPS);
        $completed = $step >= self::TOTAL_STEPS;

        return $this->customers->update($customer, [
            'onboarding_step' => $completed ? self::TOTAL_STEPS : $nextStep,
            'profile_completed' => $completed,
        ]);
    }

    protected function saveIdentification(Customer $customer, array $data): void
    {
        Identification::query()->updateOrCreate(
            ['customer_id' => $customer->customer_id],
            collect($data)->only([
                'id_type', 'id_number', 'name_en', 'name_ar',
                'issue_date', 'expiry_date', 'id_front_url', 'id_back_url',
            ])->all()
        );
    }

    protected function saveReferences(Customer $customer, array $references): void
    {
        Reference::query()->where('customer_id', $customer->customer_id)->delete();
        foreach ($references as $ref) {
            if (! empty($ref['full_name'])) {
                Reference::query()->create([
                    'customer_id' => $customer->customer_id,
                    ...$ref,
                ]);
            }
        }
    }

    protected function saveFinancialData(Customer $customer, array $data): void
    {
        FinancialData::query()->updateOrCreate(
            ['customer_id' => $customer->customer_id],
            collect($data)->only([
                'has_income_proof', 'annual_sales_1yr', 'annual_sales_2yr',
                'org_name', 'commercial_reg_type', 'commercial_reg_num',
                'reg_start_date', 'reg_expiry_date', 'paid_in_capital',
                'org_city', 'org_address',
            ])->all()
        );
    }

    protected function saveExtraDocuments(Customer $customer, array $data): void
    {
        if (! empty($data['land'])) {
            AgriculturalLand::query()->updateOrCreate(
                ['customer_id' => $customer->customer_id],
                $data['land']
            );
        }
    }

    public function storeUpload(Customer $customer, UploadedFile $file, DocType $type, ?string $userId = null): Document
    {
        $path = $file->store('customers/'.$customer->customer_id, 'documents');

        return Document::query()->create([
            'customer_id' => $customer->customer_id,
            'doc_type' => $type,
            'file_url' => $path,
            'uploaded_by' => $userId,
        ]);
    }

    public function storeIdImage(Customer $customer, UploadedFile $file, string $side): void
    {
        $path = $file->store('customers/'.$customer->customer_id.'/id', 'documents');
        $field = $side === 'back' ? 'id_back_url' : 'id_front_url';
        Identification::query()->updateOrCreate(
            ['customer_id' => $customer->customer_id],
            [$field => $path, 'id_type' => 1, 'id_number' => Identification::query()
                ->where('customer_id', $customer->customer_id)->value('id_number') ?? 'PENDING']
        );
    }
}
