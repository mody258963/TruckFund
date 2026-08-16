<?php

namespace Tests\Feature;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Livewire\Leads\LeadsIndex;
use App\Models\User;
use App\Services\LeadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_can_be_created_and_rescheduled_with_a_call_date(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(LeadsIndex::class)
            ->set('customer_name', 'Scheduled Lead')
            ->set('follow_up_on', today()->addWeek()->toDateString())
            ->call('create')
            ->assertHasNoErrors();

        $lead = \App\Models\Lead::query()->where('customer_name', 'Scheduled Lead')->firstOrFail();
        $this->assertSame(
            today()->addWeek()->toDateString(),
            Carbon::parse($lead->follow_up_on)->toDateString(),
        );

        Livewire::test(LeadsIndex::class)
            ->call('saveFollowUp', $lead->lead_id, today()->addDays(2)->toDateString())
            ->assertHasNoErrors();

        $this->assertSame(
            today()->addDays(2)->toDateString(),
            Carbon::parse($lead->fresh()->follow_up_on)->toDateString(),
        );
    }

    public function test_due_calls_are_sorted_before_future_and_unscheduled_leads(): void
    {
        $admin = User::factory()->admin()->create();
        $service = app(LeadService::class);

        $service->create(['customer_name' => 'No call date'], $admin);
        $service->create([
            'customer_name' => 'Future call',
            'follow_up_on' => today()->addDays(3)->toDateString(),
        ], $admin);
        $service->create([
            'customer_name' => 'Overdue call',
            'follow_up_on' => today()->subDay()->toDateString(),
        ], $admin);
        $service->create([
            'customer_name' => 'Call today',
            'follow_up_on' => today()->toDateString(),
        ], $admin);

        $names = collect(app(LeadRepositoryInterface::class)
            ->paginate(15, [], $admin)
            ->items())
            ->pluck('customer_name')
            ->all();

        $this->assertSame([
            'Call today',
            'Overdue call',
            'Future call',
            'No call date',
        ], $names);
    }
}
