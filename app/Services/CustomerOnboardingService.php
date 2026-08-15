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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerOnboardingService
{
    public const TOTAL_STEPS = 6;

    /** @var list<DocType> */
    public const EXTRA_DOC_TYPES = [
        DocType::LandContract,
        DocType::AcceptancePaper,
        DocType::Other,
    ];

    public function __construct(
        protected CustomerRepositoryInterface $customers,
        protected ImageStorageService $images,
    ) {}

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

    /** Map stored onboarding_step to current wizard step (6-step flow). */
    public static function normalizeStep(int $stored): int
    {
        if ($stored >= 1 && $stored <= self::TOTAL_STEPS) {
            return $stored;
        }

        // Legacy 8-step wizard values
        if ($stored <= 2) {
            return $stored;
        }
        if ($stored <= 5) {
            return 3;
        }
        if ($stored === 6) {
            return 4;
        }
        if ($stored === 7) {
            return 5;
        }

        return self::TOTAL_STEPS;
    }

    public function saveStep(
        Customer $customer,
        int $step,
        array $data,
        ?UploadedFile $incomeProofFile = null,
        ?UploadedFile $commercialRegFile = null,
    ): Customer {
        match ($step) {
            1 => $this->customers->update($customer, collect($data)->only([
                'display_name', 'mobile_number', 'email',
            ])->all()),
            2 => $this->saveIdentification($customer, $data),
            3 => $this->customers->update($customer, collect($data)->only([
                'city', 'area', 'address',
            ])->all()),
            4 => $this->saveReferences($customer, $data['references'] ?? []),
            5 => $this->saveFinancialStep($customer, $data, $incomeProofFile, $commercialRegFile),
            6 => $this->saveExtraDocuments($customer, $data),
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

    protected function saveFinancialStep(
        Customer $customer,
        array $data,
        ?UploadedFile $incomeProofFile,
        ?UploadedFile $commercialRegFile,
    ): void {
        $this->saveFinancialData($customer, $data);

        $userId = Auth::id();

        if ($incomeProofFile) {
            $this->storeUpload($customer, $incomeProofFile, DocType::IncomeProof, $userId);
        }

        if ($commercialRegFile) {
            $this->storeUpload($customer, $commercialRegFile, DocType::CommercialReg, $userId);
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
        $stored = $this->images->store($file, 'customers/'.$customer->customer_id);

        return Document::query()->create([
            'customer_id' => $customer->customer_id,
            'doc_type' => $type,
            'file_url' => $stored['path'],
            'uploaded_by' => $userId,
        ]);
    }

    public function deleteDocument(Customer $customer, string $docId): void
    {
        $doc = Document::query()
            ->where('customer_id', $customer->customer_id)
            ->where('doc_id', $docId)
            ->firstOrFail();

        if (! in_array($doc->doc_type, self::EXTRA_DOC_TYPES, true)) {
            abort(403);
        }

        if ($doc->file_url) {
            Storage::disk(ImageStorageService::DISK)->delete($doc->file_url);
        }

        $doc->delete();
    }

    public function storeIdImage(Customer $customer, UploadedFile $file, string $side): void
    {
        $stored = $this->images->store($file, 'customers/'.$customer->customer_id.'/id');
        $field = $side === 'back' ? 'id_back_url' : 'id_front_url';

        // id_number is left untouched; it is unique, so a placeholder here would
        // collide across customers. The number is saved by saveIdentification().
        Identification::query()->updateOrCreate(
            ['customer_id' => $customer->customer_id],
            [$field => $stored['path'], 'id_type' => 1],
        );
    }
}
