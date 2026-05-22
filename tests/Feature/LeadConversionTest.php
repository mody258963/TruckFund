<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_converts_to_customer(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $lead = app(LeadService::class)->create([
            'customer_name' => 'Test Customer',
            'phone' => '01000000001',
        ]);

        $customer = app(LeadService::class)->convertToCustomer($lead);

        $this->assertNotNull($customer->customer_id);
        $lead->refresh();
        $this->assertEquals($customer->customer_id, $lead->customer_id);
        $this->assertEquals(LeadStatus::ResolvedProfileCreated, $lead->status);
    }
}
