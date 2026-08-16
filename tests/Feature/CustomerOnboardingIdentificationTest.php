<?php

namespace Tests\Feature;

use App\Enums\DocType;
use App\Livewire\Customers\CustomerOnboardingWizard;
use App\Models\Customer;
use App\Models\Document;
use App\Models\FinancialData;
use App\Models\Identification;
use App\Models\User;
use App\Services\CustomerOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerOnboardingIdentificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_photos_for_two_customers_do_not_collide(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());
        $onboarding = app(CustomerOnboardingService::class);

        foreach (['First Customer', 'Second Customer'] as $name) {
            $customer = Customer::query()->create([
                'display_name' => $name,
                'mobile_number' => '01000000000',
            ]);

            $onboarding->storeIdImage($customer, $this->fakeIdPhoto(), 'front');

            $identification = Identification::query()
                ->where('customer_id', $customer->customer_id)
                ->firstOrFail();

            $this->assertNull($identification->id_number);
            $this->assertNotNull($identification->id_front_url);
        }

        $this->assertSame(2, Identification::query()->count());
    }

    public function test_duplicate_id_number_is_a_validation_error(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $existing = Customer::query()->create([
            'display_name' => 'Existing Customer',
            'mobile_number' => '01000000001',
        ]);
        Identification::query()->create([
            'customer_id' => $existing->customer_id,
            'id_type' => 1,
            'id_number' => '12345678901234',
        ]);

        $customer = Customer::query()->create([
            'display_name' => 'New Customer',
            'mobile_number' => '01000000002',
            'onboarding_step' => 2,
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->set('form.id_number', '12345678901234')
            ->call('saveStep')
            ->assertHasErrors(['form.id_number' => 'unique']);

        $this->assertSame(1, Identification::query()->count());
    }

    public function test_id_number_is_saved_after_uploading_a_photo(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $customer = Customer::query()->create([
            'display_name' => 'Photo First Customer',
            'mobile_number' => '01000000003',
            'onboarding_step' => 2,
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->set('form.id_number', '99887766554433')
            ->set('idFront', $this->fakeIdPhoto())
            ->call('saveStep')
            ->assertHasNoErrors();

        $identification = Identification::query()
            ->where('customer_id', $customer->customer_id)
            ->firstOrFail();

        $this->assertSame('99887766554433', $identification->id_number);
        $this->assertNotNull($identification->id_front_url);
    }

    public function test_final_step_completes_without_extra_documents(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $customer = Customer::query()->create([
            'display_name' => 'Finishing Customer',
            'mobile_number' => '01000000004',
            'onboarding_step' => CustomerOnboardingService::TOTAL_STEPS,
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->call('saveStep')
            ->assertHasNoErrors()
            ->assertRedirect(route('customers.show', $customer));

        $this->assertTrue($customer->fresh()->profile_completed);
    }

    public function test_large_commercial_registration_type_is_saved(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $customer = Customer::query()->create([
            'display_name' => 'Commercial Registration Customer',
            'mobile_number' => '01000000007',
            'onboarding_step' => 5,
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->set('form.commercial_reg_type', '153525')
            ->set('form.commercial_reg_num', '153525')
            ->set('form.paid_in_capital', 50000)
            ->call('saveStep')
            ->assertHasNoErrors()
            ->assertSet('step', 6);

        $financialData = FinancialData::query()
            ->where('customer_id', $customer->customer_id)
            ->firstOrFail();

        $this->assertSame('153525', $financialData->commercial_reg_type);
        $this->assertSame('153525', $financialData->commercial_reg_num);
    }

    public function test_business_step_stores_several_files_per_field(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $customer = Customer::query()->create([
            'display_name' => 'Business Documents Customer',
            'mobile_number' => '01000000008',
            'onboarding_step' => 5,
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->set('form.org_name', 'صلاح زكي الملط')
            ->set('incomeProofFiles', [
                UploadedFile::fake()->create('income-1.jpg', 40, 'image/jpeg'),
                UploadedFile::fake()->create('income-2.pdf', 40, 'application/pdf'),
            ])
            ->set('commercialRegFiles', [
                UploadedFile::fake()->create('reg-1.jpg', 40, 'image/jpeg'),
                UploadedFile::fake()->create('reg-2.jpg', 40, 'image/jpeg'),
                UploadedFile::fake()->create('reg-3.jpg', 40, 'image/jpeg'),
            ])
            ->call('saveStep')
            ->assertHasNoErrors()
            ->assertSet('step', 6)
            ->assertSet('incomeProofFiles', [])
            ->assertSet('commercialRegFiles', []);

        $documents = Document::query()->where('customer_id', $customer->customer_id)->get();

        $this->assertSame(2, $documents->where('doc_type', DocType::IncomeProof)->count());
        $this->assertSame(3, $documents->where('doc_type', DocType::CommercialReg)->count());

        foreach ($documents as $document) {
            $this->assertTrue(Storage::disk('documents')->exists($document->file_url));
        }
    }

    public function test_business_step_rejects_an_unsupported_file_type(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $customer = Customer::query()->create([
            'display_name' => 'Bad Upload Customer',
            'mobile_number' => '01000000011',
            'onboarding_step' => 5,
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->set('commercialRegFiles', [
                UploadedFile::fake()->create('reg-1.jpg', 40, 'image/jpeg'),
                UploadedFile::fake()->create('virus.exe', 40, 'application/octet-stream'),
            ])
            ->call('saveStep')
            ->assertHasErrors('commercialRegFiles.1');

        $this->assertSame(0, Document::query()->where('customer_id', $customer->customer_id)->count());
    }

    public function test_extra_document_can_be_removed_on_the_final_step(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $customer = Customer::query()->create([
            'display_name' => 'Removing Customer',
            'mobile_number' => '01000000006',
            'onboarding_step' => CustomerOnboardingService::TOTAL_STEPS,
        ]);

        Storage::disk('documents')->put('customers/other-doc.jpg', 'fake');
        $doc = Document::query()->create([
            'customer_id' => $customer->customer_id,
            'doc_type' => DocType::Other,
            'file_url' => 'customers/other-doc.jpg',
            'uploaded_at' => now(),
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->assertSet('step', CustomerOnboardingService::TOTAL_STEPS)
            ->call('removeDocument', $doc->doc_id)
            ->assertHasNoErrors()
            ->assertDontSee(basename($doc->file_url));

        $this->assertSame(0, Document::query()->where('customer_id', $customer->customer_id)->count());
        $this->assertFalse(Storage::disk('documents')->exists('customers/other-doc.jpg'));
    }

    public function test_final_step_stores_several_extra_documents(): void
    {
        Storage::fake('documents');

        $this->actingAs(User::factory()->create());

        $customer = Customer::query()->create([
            'display_name' => 'Documents Customer',
            'mobile_number' => '01000000005',
            'onboarding_step' => CustomerOnboardingService::TOTAL_STEPS,
        ]);

        Livewire::test(CustomerOnboardingWizard::class, ['customer' => $customer])
            ->set('extraDocFiles', [
                UploadedFile::fake()->create('land-contract.pdf', 40, 'application/pdf'),
                UploadedFile::fake()->create('acceptance.jpg', 40, 'image/jpeg'),
            ])
            ->call('saveStep')
            ->assertHasNoErrors()
            ->assertSet('extraDocFiles', [])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame(2, Document::query()->where('customer_id', $customer->customer_id)->count());
        $this->assertTrue($customer->fresh()->profile_completed);
    }

    private function fakeIdPhoto(): UploadedFile
    {
        return UploadedFile::fake()->create('id-front.jpg', 60, 'image/jpeg');
    }
}
