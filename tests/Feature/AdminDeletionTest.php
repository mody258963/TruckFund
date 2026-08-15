<?php

namespace Tests\Feature;

use App\Enums\DocType;
use App\Livewire\Customers\CustomerShow;
use App\Livewire\Leads\LeadShow;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerOnboardingService;
use App\Services\FinanceApplicationService;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_lead_without_deleting_its_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $lead = app(LeadService::class)->create([
            'customer_name' => 'Converted Lead',
            'phone' => '01000000010',
        ]);
        $customer = app(LeadService::class)->convertToCustomer($lead);

        Livewire::test(LeadShow::class, ['lead' => $lead])
            ->call('delete')
            ->assertRedirect(route('leads.index'));

        $this->assertDatabaseMissing('leads', ['lead_id' => $lead->lead_id]);
        $this->assertDatabaseHas('customers', [
            'customer_id' => $customer->customer_id,
            'lead_id' => null,
        ]);
    }

    public function test_non_admin_cannot_delete_a_lead(): void
    {
        $sales = User::factory()->create();
        $lead = app(LeadService::class)->create([
            'customer_name' => 'Protected Lead',
            'phone' => '01000000011',
            'assigned_user_id' => $sales->user_id,
        ], $sales);

        $this->actingAs($sales);

        Livewire::test(LeadShow::class, ['lead' => $lead])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('leads', ['lead_id' => $lead->lead_id]);
    }

    public function test_admin_can_delete_customer_records_and_stored_files(): void
    {
        Storage::fake('documents');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $lead = app(LeadService::class)->create([
            'customer_name' => 'Customer To Delete',
            'phone' => '01000000012',
        ]);
        $customer = app(LeadService::class)->convertToCustomer($lead);

        $onboarding = app(CustomerOnboardingService::class);
        $onboarding->storeIdImage($customer, $this->fakeFile('id.jpg', 'image/jpeg'), 'front');
        $document = $onboarding->storeUpload(
            $customer,
            $this->fakeFile('contract.pdf', 'application/pdf'),
            DocType::LandContract,
            $admin->user_id,
        );

        $applicationService = app(FinanceApplicationService::class);
        $application = $applicationService->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $admin->user_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);
        $applicationDocument = $applicationService->uploadDocument(
            $application,
            $this->fakeFile('acceptance.pdf', 'application/pdf'),
            DocType::AcceptancePaper,
            $admin->user_id,
        );

        $idPath = $customer->identification()->firstOrFail()->id_front_url;
        $disk = Storage::disk('documents');
        $this->assertTrue($disk->exists($idPath));
        $this->assertTrue($disk->exists($document->file_url));
        $this->assertTrue($disk->exists($applicationDocument->file_url));

        Livewire::test(CustomerShow::class, ['customer' => $customer])
            ->call('delete')
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', ['customer_id' => $customer->customer_id]);
        $this->assertDatabaseMissing('finance_applications', ['app_id' => $application->app_id]);
        $this->assertDatabaseHas('leads', [
            'lead_id' => $lead->lead_id,
            'customer_id' => null,
        ]);
        $this->assertFalse($disk->exists($idPath));
        $this->assertFalse($disk->exists($document->file_url));
        $this->assertFalse($disk->exists($applicationDocument->file_url));
    }

    public function test_non_admin_cannot_delete_a_customer(): void
    {
        $sales = User::factory()->create();
        $customer = Customer::query()->create([
            'display_name' => 'Protected Customer',
            'mobile_number' => '01000000013',
        ]);

        $this->actingAs($sales);

        Livewire::test(CustomerShow::class, ['customer' => $customer])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('customers', ['customer_id' => $customer->customer_id]);
    }

    private function fakeFile(string $name, string $mime): UploadedFile
    {
        return UploadedFile::fake()->create($name, 10, $mime);
    }
}
