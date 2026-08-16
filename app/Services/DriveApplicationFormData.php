<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\FinanceApplication;
use Illuminate\Support\Facades\File;

class DriveApplicationFormData
{
    /**
     * Map a finance application / CRM customer onto the DRIVE paper form fields.
     *
     * @return array<string, mixed>
     */
    public function fromApplication(FinanceApplication $application): array
    {
        $customer = $application->customer;
        $ident = $customer?->identification;
        $fin = $customer?->financialData;
        $product = $application->autoProduct;
        $reference = $customer?->references?->first();

        $nameEn = trim((string) ($ident?->name_en ?: $customer?->display_name));
        $nameAr = trim((string) ($ident?->name_ar ?: ''));

        $address = collect([
            $customer?->address,
            $customer?->area,
            $customer?->city,
        ])->map(fn ($part) => trim((string) $part))->filter()->implode('، ');

        // Older profiles sometimes only captured work/org location.
        if ($address === '') {
            $address = collect([
                $fin?->org_address,
                $fin?->org_city,
            ])->map(fn ($part) => trim((string) $part))->filter()->implode('، ');
        }

        $workAddress = collect([
            $fin?->org_address,
            $fin?->org_city,
        ])->map(fn ($part) => trim((string) $part))->filter()->implode('، ');

        $monthlyIncome = $application->monthly_income;
        if ($monthlyIncome === null && $fin?->annual_sales_1yr) {
            $monthlyIncome = ((float) $fin->annual_sales_1yr) / 12;
        }

        $refAddress = '';
        if ($reference) {
            if ($reference->same_address) {
                $refAddress = $address;
            }
        }

        $isSelfEmployed = filled($fin?->org_name);

        return [
            'showroom' => (string) config('truckfund.drive_form_showroom', 'sara gamal'),
            'date' => now()->format('d/m/Y'),
            'title' => 'Mr',
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'name_combined' => collect([$nameEn, $nameAr])->filter()->implode('  |  '),
            'dob' => $customer?->date_of_birth?->format('d/m/Y') ?? '',
            'gender' => $customer?->gender === Gender::Female ? 'F' : 'M',
            'nationality' => 'Egyptian',
            'id_type' => 'Egyptian',
            'id_number' => (string) ($ident?->id_number ?? ''),
            'home_address' => $address,
            'home_ownership' => null,
            'home_duration' => '',
            'phone_mobile' => (string) ($customer?->mobile_number ?? ''),
            // Email intentionally left blank on the DRIVE form.
            'email' => '',
            'prev_address' => '',
            'prev_ownership' => null,
            'prev_duration' => '',
            'ref_name' => (string) ($reference?->full_name ?? ''),
            'ref_relation' => (string) ($reference?->relation ?? ''),
            'ref_address' => $refAddress,
            'ref_phone' => (string) ($reference?->mobile ?? ''),
            'employment' => $isSelfEmployed ? 'Self-Employed' : 'Salaried',
            'job_title' => $isSelfEmployed ? 'صاحب منشأة' : (string) ($customer?->occupation ?? ''),
            'job_duration' => '',
            'company_name' => (string) ($fin?->org_name ?: $customer?->organization_name ?: ''),
            'business_type' => (string) ($fin?->commercial_reg_type ?? ''),
            'work_address' => $workAddress,
            'office_phone' => '',
            'work_email' => '',
            'income_fixed' => $this->money($monthlyIncome),
            'income_variable' => '',
            'income_total' => $this->money($monthlyIncome),
            'brand' => (string) ($product?->brand ?? ''),
            'model' => (string) ($product?->name ?? ''),
            'year' => $product?->model_year ? (string) $product->model_year : '',
            'color' => '',
            'engine_cc' => '',
            'options' => '',
            'price' => $this->money($application->total_truck_price ?? $product?->price),
            'down_payment' => $this->money($application->down_payment),
            'tenor_years' => '',
            'docs_id' => (bool) ($ident?->id_front_url || $ident?->id_back_url),
            'docs_residence' => $customer?->documents?->isNotEmpty() ?? false,
            'comments' => $application->customer?->lead?->lead_number
                ? 'Lead '.$application->customer->lead->lead_number
                : '',
            'sales_officer' => (string) ($application->user?->full_name ?? ''),
        ];
    }

    protected function money(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 0, '.', ',');
    }

    public function applicantTemplate(): string
    {
        return resource_path('pdf-forms/drive-application-applicant.jpg');
    }

    public function employmentTemplate(): string
    {
        return resource_path('pdf-forms/drive-application-employment.jpg');
    }

    public function ensureTemplatesExist(): void
    {
        foreach ([$this->applicantTemplate(), $this->employmentTemplate()] as $path) {
            if (! File::exists($path)) {
                throw new \RuntimeException("Missing DRIVE form template: {$path}");
            }
        }
    }
}
