<?php

namespace Tests\Feature;

use App\Livewire\Customers\CustomerOnboardingWizard;
use App\Models\Customer;
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

    private function fakeIdPhoto(): UploadedFile
    {
        return UploadedFile::fake()->create('id-front.jpg', 60, 'image/jpeg');
    }
}
