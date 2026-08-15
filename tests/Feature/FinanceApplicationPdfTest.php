<?php

namespace Tests\Feature;

use App\Models\AutoProduct;
use App\Models\Customer;
use App\Models\FinancialProduct;
use App\Models\Identification;
use App\Models\Merchant;
use App\Models\User;
use App\Services\FinanceApplicationPdfService;
use App\Services\FinanceApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceApplicationPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_download_requires_complete_financial_details(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $customer = Customer::query()->create([
            'display_name' => 'PDF Customer',
            'mobile_number' => '01001112233',
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('finance.pdf', $app))
            ->assertStatus(422);
    }

    public function test_pdf_download_returns_pdf_for_complete_application(): void
    {
        Storage::fake('documents');

        $user = User::factory()->financeOfficer()->create();
        $merchant = Merchant::query()->create([
            'name' => 'Test Bank',
            'type' => 1,
            'is_active' => true,
        ]);
        $autoProduct = AutoProduct::query()->create([
            'brand' => 'Volvo',
            'name' => 'FH16',
            'type' => 1,
            'chassis' => 'CH-PDF-001',
            'price' => 600000,
            'is_active' => true,
        ]);
        $financialProduct = FinancialProduct::query()->create([
            'name' => 'Truck Loan 60m',
            'product_code' => 'PDF-LOAN-01',
            'percentage' => 18.5,
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'display_name' => 'PDF Customer',
            'mobile_number' => '01001112233',
            'city' => 'Cairo',
            'address' => '123 Main St',
        ]);

        Identification::query()->create([
            'customer_id' => $customer->customer_id,
            'id_type' => 1,
            'id_number' => '1234567890123',
            'name_en' => 'PDF Customer',
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'financial_merchant_id' => $merchant->merchant_id,
            'auto_product_id' => $autoProduct->id,
            'financial_product_id' => $financialProduct->product_id,
            'down_payment' => 100000,
            'total_loan_amount' => 500000,
            'total_truck_price' => 600000,
            'monthly_income' => 25000,
        ]);

        $pdfService = app(FinanceApplicationPdfService::class);
        $this->assertTrue($pdfService->canGenerate($app));

        $response = $this->actingAs($user)->get(route('finance.pdf', $app));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
