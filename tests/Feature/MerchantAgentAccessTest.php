<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\FunderReviewStatus;
use App\Enums\UserRole;
use App\Livewire\Finance\FinanceApplicationShow;
use App\Livewire\Finance\FinanceApplicationsIndex;
use App\Models\Customer;
use App\Models\User;
use App\Services\FinanceApplicationService;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantAgentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_merchant_agent_user(): void
    {
        $admin = User::factory()->admin()->create();

        $agent = app(UserManagementService::class)->create($admin, [
            'full_name' => 'External Funder',
            'email' => 'funder@ashmawyfund.test',
            'phone' => '01001112233',
            'role' => UserRole::MerchantAgent->value,
            'password' => 'password123',
        ]);

        $this->assertTrue($agent->isMerchantAgent());
    }

    public function test_merchant_agent_can_view_finance_but_not_leads_or_catalog(): void
    {
        $agent = User::factory()->merchantAgent()->create();
        $this->actingAs($agent);

        $this->get(route('finance.index'))->assertOk();
        $this->get(route('customers.index'))->assertOk();
        $this->get(route('leads.index'))->assertForbidden();
        $this->get(route('admin.catalog', 'merchants'))->assertForbidden();
        $this->get(route('dashboard'))->assertRedirect(route('finance.index'));
    }

    public function test_merchant_agent_can_save_funder_review_but_cannot_create_application(): void
    {
        $officer = User::factory()->financeOfficer()->create();
        $agent = User::factory()->merchantAgent()->create();

        $customer = Customer::query()->create([
            'display_name' => 'Funder Customer',
            'mobile_number' => '01009998877',
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $officer->user_id,
            'down_payment' => 100000,
            'total_loan_amount' => 500000,
            'total_truck_price' => 600000,
        ]);

        $this->actingAs($agent);

        Livewire::test(FinanceApplicationsIndex::class)
            ->assertSet('canCreate', false);

        Livewire::test(FinanceApplicationShow::class, ['application' => $app])
            ->set('funderStatus', FunderReviewStatus::NeedsAction->value)
            ->set('funderFeedback', 'Please upload the commercial register.')
            ->call('saveFunderReview')
            ->assertHasNoErrors();

        $app->refresh();
        $this->assertEquals(FunderReviewStatus::NeedsAction, $app->funder_status);
        $this->assertSame('Please upload the commercial register.', $app->funder_feedback);
        $this->assertSame($agent->user_id, $app->funder_reviewed_by);
    }

    public function test_booking_confirmed_sets_reentry_due_in_two_months(): void
    {
        $officer = User::factory()->financeOfficer()->create();
        $this->actingAs($officer);

        $customer = Customer::query()->create([
            'display_name' => 'Reentry Customer',
            'mobile_number' => '01005556677',
        ]);

        $service = app(FinanceApplicationService::class);
        $app = $service->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $officer->user_id,
            'down_payment' => 100000,
            'total_loan_amount' => 500000,
            'total_truck_price' => 600000,
        ]);

        $app = $service->update($app, ['status' => ApplicationStatus::Accepted->value]);
        $app = $service->confirmBooking($app, '2026-08-21');

        $this->assertEquals(ApplicationStatus::BookingConfirmed, $app->status);
        $this->assertSame('2026-10-21', $app->reentry_due_at?->format('Y-m-d'));
    }

    public function test_complete_sets_reentry_due_when_missing(): void
    {
        $officer = User::factory()->financeOfficer()->create();
        $service = app(FinanceApplicationService::class);

        $customer = Customer::query()->create([
            'display_name' => 'Complete Customer',
            'mobile_number' => '01003334455',
        ]);

        $app = $service->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $officer->user_id,
            'down_payment' => 100000,
            'total_loan_amount' => 500000,
            'total_truck_price' => 600000,
        ]);

        $app = $service->update($app, ['status' => ApplicationStatus::DocsUploaded->value]);
        $app = $service->complete($app);

        $this->assertEquals(ApplicationStatus::Completed, $app->status);
        $this->assertNotNull($app->reentry_due_at);
        $this->assertSame(now()->addMonths(2)->toDateString(), $app->reentry_due_at->format('Y-m-d'));
    }
}
