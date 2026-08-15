<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Livewire\Finance\FinanceApplicationShow;
use App\Models\AutoProduct;
use App\Models\Customer;
use App\Models\FinancialProduct;
use App\Models\Merchant;
use App\Models\User;
use App\Services\FinanceApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_submit_moves_to_under_review(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'display_name' => 'Finance Test',
            'mobile_number' => '01009998877',
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 100000,
            'total_loan_amount' => 500000,
            'total_truck_price' => 600000,
        ]);

        $app = app(FinanceApplicationService::class)->submitForReview($app);

        $this->assertEquals(ApplicationStatus::UnderReview, $app->status);
    }

    public function test_draft_rejects_merchant_id_that_is_not_a_merchant(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'display_name' => 'Finance Test',
            'mobile_number' => '01009998877',
        ]);

        $autoProduct = AutoProduct::query()->create([
            'brand' => 'Volvo',
            'name' => 'FH16',
            'type' => 1,
            'chassis' => 'CH-FK-001',
            'price' => 600000,
            'is_active' => true,
        ]);

        $financialProduct = FinancialProduct::query()->create([
            'name' => 'Truck Loan 60m',
            'product_code' => 'FK-LOAN-01',
            'percentage' => 18.5,
            'is_active' => true,
        ]);

        Merchant::query()->create([
            'name' => 'Test Bank',
            'type' => 1,
            'is_active' => true,
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);

        Livewire::test(FinanceApplicationShow::class, ['application' => $app])
            ->set('form.financial_merchant_id', $financialProduct->product_id)
            ->set('form.auto_product_id', $autoProduct->id)
            ->set('form.financial_product_id', $financialProduct->product_id)
            ->set('form.total_truck_price', 4000000)
            ->set('form.down_payment', 1000000)
            ->set('form.total_loan_amount', 3000000)
            ->call('saveDraft')
            ->assertHasErrors(['form.financial_merchant_id' => 'exists']);

        $this->assertNull($app->fresh()->financial_merchant_id);
    }

    public function test_several_acceptance_documents_upload_in_one_submission(): void
    {
        Storage::fake('documents');

        $user = User::factory()->financeOfficer()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'display_name' => 'Finance Test',
            'mobile_number' => '01009998877',
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 100000,
            'total_loan_amount' => 500000,
            'total_truck_price' => 600000,
        ]);

        Livewire::test(FinanceApplicationShow::class, ['application' => $app])
            ->set('acceptanceDocs', [
                UploadedFile::fake()->create('acceptance-1.jpg', 100, 'image/jpeg'),
                UploadedFile::fake()->create('acceptance-2.png', 100, 'image/png'),
                UploadedFile::fake()->create('acceptance-3.pdf', 100, 'application/pdf'),
            ])
            ->call('uploadDoc')
            ->assertHasNoErrors();

        $this->assertCount(3, $app->fresh()->applicationDocuments);
        $this->assertEquals(ApplicationStatus::DocsUploaded, $app->fresh()->status);
    }

    public function test_acceptance_document_upload_rejects_unsupported_file_type(): void
    {
        Storage::fake('documents');

        $user = User::factory()->financeOfficer()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'display_name' => 'Finance Test',
            'mobile_number' => '01009998877',
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 100000,
            'total_loan_amount' => 500000,
            'total_truck_price' => 600000,
        ]);

        Livewire::test(FinanceApplicationShow::class, ['application' => $app])
            ->set('acceptanceDocs', [
                UploadedFile::fake()->create('acceptance-1.jpg', 100, 'image/jpeg'),
                UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->call('uploadDoc')
            ->assertHasErrors('acceptanceDocs.1');

        $this->assertCount(0, $app->fresh()->applicationDocuments);
    }

    public function test_application_number_is_not_reused_after_a_deletion(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $customer = Customer::query()->create([
            'display_name' => 'Numbering Test',
            'mobile_number' => '01009998878',
        ]);
        $service = app(FinanceApplicationService::class);
        $data = [
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ];

        $first = $service->createDraft($data);
        $second = $service->createDraft($data);
        $first->delete();
        $third = $service->createDraft($data);

        $this->assertStringEndsWith('-000002', $second->app_number);
        $this->assertStringEndsWith('-000003', $third->app_number);
    }
}
