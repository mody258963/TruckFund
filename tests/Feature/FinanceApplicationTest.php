<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Customer;
use App\Models\User;
use App\Services\FinanceApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
