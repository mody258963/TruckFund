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
}
