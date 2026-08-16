<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Livewire\Finance\FinanceApplicationShow;
use App\Models\AutoProduct;
use App\Models\Customer;
use App\Models\FinancialProduct;
use App\Models\Merchant;
use App\Models\User;
use App\Services\DriveApplicationFormData;
use App\Services\FinanceApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            'model' => 'FH',
            'name' => 'FH16',
            'type' => 1,
            'chassis' => 'CH-FK-001',
            'price' => 600000,
            'model_year' => 2026,
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
            ->set('selectedVehicleIds', [$autoProduct->id])
            ->set('form.financial_product_id', $financialProduct->product_id)
            ->set('form.total_truck_price', 4000000)
            ->set('form.down_payment', 1000000)
            ->set('form.total_loan_amount', 3000000)
            ->call('saveDraft')
            ->assertHasErrors(['form.financial_merchant_id' => 'exists']);

        $this->assertNull($app->fresh()->financial_merchant_id);
    }

    public function test_vehicles_are_chosen_year_then_model_then_name(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'display_name' => 'Vehicle Choice Customer',
            'mobile_number' => '01009998878',
        ]);

        $head = AutoProduct::query()->create([
            'brand' => 'Volvo',
            'model' => 'FH',
            'name' => 'FH 500 Head',
            'type' => 1,
            'chassis' => 'CH-HEAD-2025',
            'price' => 2000000,
            'model_year' => 2025,
            'is_active' => true,
        ]);
        $trailer = AutoProduct::query()->create([
            'brand' => 'Volvo',
            'model' => 'Trailer',
            'name' => 'Lowbed Tail',
            'type' => 14,
            'chassis' => 'CH-TAIL-2025',
            'price' => 800000,
            'model_year' => 2025,
            'is_active' => true,
        ]);
        $otherYear = AutoProduct::query()->create([
            'brand' => 'Volvo',
            'model' => 'FH',
            'name' => 'FH 500 2026',
            'type' => 1,
            'chassis' => 'CH-HEAD-2026',
            'price' => 2200000,
            'model_year' => 2026,
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
            ->assertSee('2025')
            ->assertSee('2026')
            ->assertDontSee($head->name)
            ->set('selectedModelYear', 2025)
            ->assertSee('FH')
            ->assertSee('Trailer')
            ->assertDontSee($head->name)
            ->set('selectedModel', 'FH')
            ->assertSee($head->name)
            ->assertDontSee($trailer->name)
            ->assertDontSee($otherYear->name)
            ->set('pendingVehicleId', $head->id)
            ->call('addVehicle')
            ->assertSet('selectedVehicleIds', [$head->id])
            ->set('selectedModel', 'Trailer')
            ->set('pendingVehicleId', $trailer->id)
            ->call('addVehicle')
            ->assertSet('selectedVehicleIds', [$head->id, $trailer->id]);
    }

    public function test_application_accepts_up_to_three_distinct_vehicles_and_rejects_a_fourth(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'display_name' => 'Multi Vehicle Customer',
            'mobile_number' => '01009998879',
        ]);

        $vehicles = collect([
            ['chassis' => 'CH-MV-1', 'name' => 'Head One', 'model' => 'Head'],
            ['chassis' => 'CH-MV-2', 'name' => 'Tail One', 'model' => 'Tail'],
            ['chassis' => 'CH-MV-3', 'name' => 'Box One', 'model' => 'Box'],
            ['chassis' => 'CH-MV-4', 'name' => 'Extra One', 'model' => 'Extra'],
        ])->map(fn (array $data) => AutoProduct::query()->create([
            'brand' => 'TestBrand',
            'model' => $data['model'],
            'name' => $data['name'],
            'type' => 1,
            'chassis' => $data['chassis'],
            'price' => 100000,
            'model_year' => 2025,
            'is_active' => true,
        ]));

        $merchant = Merchant::query()->create([
            'name' => 'Test Bank',
            'type' => 1,
            'is_active' => true,
        ]);
        $financialProduct = FinancialProduct::query()->create([
            'name' => 'Loan',
            'product_code' => 'MV-LOAN-01',
            'percentage' => 18.5,
            'is_active' => true,
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);

        $component = Livewire::test(FinanceApplicationShow::class, ['application' => $app]);

        foreach ($vehicles->take(3) as $vehicle) {
            $component
                ->set('selectedModelYear', 2025)
                ->set('selectedModel', $vehicle->model)
                ->set('pendingVehicleId', $vehicle->id)
                ->call('addVehicle')
                ->assertHasNoErrors();
        }

        $component
            ->set('selectedModelYear', 2025)
            ->set('selectedModel', $vehicles[3]->model)
            ->set('pendingVehicleId', $vehicles[3]->id)
            ->call('addVehicle')
            ->assertHasErrors('pendingVehicleId');

        $component
            ->set('form.financial_merchant_id', $merchant->merchant_id)
            ->set('form.financial_product_id', $financialProduct->product_id)
            ->set('form.total_truck_price', 900000)
            ->set('form.down_payment', 100000)
            ->set('form.total_loan_amount', 800000)
            ->call('saveDraft')
            ->assertHasNoErrors();

        $fresh = $app->fresh()->load('autoProducts');
        $this->assertCount(3, $fresh->autoProducts);
        $this->assertSame($vehicles[0]->id, $fresh->auto_product_id);
        $this->assertEqualsCanonicalizing(
            $vehicles->take(3)->pluck('id')->all(),
            $fresh->autoProducts->pluck('id')->all(),
        );
    }

    public function test_duplicate_vehicle_cannot_be_added(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'display_name' => 'Duplicate Vehicle Customer',
            'mobile_number' => '01009998880',
        ]);

        $vehicle = AutoProduct::query()->create([
            'brand' => 'Toyota',
            'model' => 'Coaster',
            'name' => 'Coaster',
            'type' => 17,
            'chassis' => 'CH-DUP-1',
            'price' => 1000000,
            'model_year' => 2025,
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
            ->set('selectedModelYear', 2025)
            ->set('selectedModel', 'Coaster')
            ->set('pendingVehicleId', $vehicle->id)
            ->call('addVehicle')
            ->assertHasNoErrors()
            ->set('pendingVehicleId', $vehicle->id)
            ->call('addVehicle')
            ->assertHasErrors('pendingVehicleId');
    }

    public function test_legacy_single_vehicle_application_still_maps_for_pdf(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $customer = Customer::query()->create([
            'display_name' => 'Legacy Vehicle Customer',
            'mobile_number' => '01009998881',
        ]);
        $vehicle = AutoProduct::query()->create([
            'brand' => 'MAN',
            'model' => 'TGX',
            'name' => 'TGX 18.510',
            'type' => 1,
            'chassis' => 'CH-LEGACY-1',
            'price' => 1500000,
            'model_year' => 2024,
            'is_active' => true,
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'auto_product_id' => $vehicle->id,
            'down_payment' => 100000,
            'total_loan_amount' => 1400000,
            'total_truck_price' => 1500000,
        ]);

        // Simulate a pre-pivot record that only has the legacy FK.
        DB::table('finance_application_auto_product')->where('app_id', $app->app_id)->delete();
        $app->unsetRelation('autoProducts');

        $mapped = app(DriveApplicationFormData::class)->fromApplication($app->fresh()->load('autoProduct'));

        $this->assertCount(1, $mapped['vehicles']);
        $this->assertSame('MAN', $mapped['vehicles'][0]['brand']);
        $this->assertSame('TGX 18.510', $mapped['vehicles'][0]['name']);
        $this->assertSame('TGX', $mapped['vehicles'][0]['model']);
        $this->assertSame('2024', $mapped['vehicles'][0]['year']);
    }

    public function test_pdf_mapping_keeps_name_and_model_separate_for_multiple_vehicles(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $customer = Customer::query()->create([
            'display_name' => 'Mapped Vehicles Customer',
            'mobile_number' => '01009998882',
        ]);
        $head = AutoProduct::query()->create([
            'brand' => 'Mercedes-Benz',
            'model' => 'Actros',
            'name' => 'Actros L Head',
            'type' => 1,
            'chassis' => 'CH-MAP-HEAD',
            'price' => 2500000,
            'model_year' => 2025,
            'is_active' => true,
        ]);
        $tail = AutoProduct::query()->create([
            'brand' => 'Mercedes-Benz',
            'model' => 'Trailer',
            'name' => 'Curtainside Tail',
            'type' => 14,
            'chassis' => 'CH-MAP-TAIL',
            'price' => 900000,
            'model_year' => 2025,
            'is_active' => true,
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 300000,
            'total_loan_amount' => 3100000,
            'total_truck_price' => 3400000,
        ]);

        $app = app(FinanceApplicationService::class)->update($app, [
            'down_payment' => 300000,
            'total_loan_amount' => 3100000,
            'total_truck_price' => 3400000,
        ], [$head->id, $tail->id]);

        $mapped = app(DriveApplicationFormData::class)->fromApplication($app);

        $this->assertCount(2, $mapped['vehicles']);
        $this->assertSame('Actros L Head', $mapped['vehicles'][0]['name']);
        $this->assertSame('Actros', $mapped['vehicles'][0]['model']);
        $this->assertSame('Curtainside Tail', $mapped['vehicles'][1]['name']);
        $this->assertSame('Trailer', $mapped['vehicles'][1]['model']);
        $this->assertSame('3,400,000', $mapped['price']);
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

    public function test_pdf_download_button_remains_available_after_reload(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $merchant = Merchant::query()->create([
            'name' => 'Test Bank',
            'type' => 1,
            'is_active' => true,
        ]);
        $autoProduct = AutoProduct::query()->create([
            'brand' => 'Volvo',
            'model' => 'FH',
            'name' => 'FH16',
            'type' => 1,
            'chassis' => 'CH-PDF-BTN-001',
            'price' => 600000,
            'model_year' => 2025,
            'is_active' => true,
        ]);
        $financialProduct = FinancialProduct::query()->create([
            'name' => 'Truck Loan 60m',
            'product_code' => 'PDF-BTN-01',
            'percentage' => 18.5,
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'display_name' => 'PDF Button Customer',
            'mobile_number' => '01001112234',
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
        ]);
        $app = app(FinanceApplicationService::class)->update($app, [], [$autoProduct->id]);

        $this->actingAs($user);

        Livewire::test(FinanceApplicationShow::class, ['application' => $app])
            ->assertSee(__('finance.download_pdf'), false)
            ->assertSee(route('finance.pdf', $app), false);

        Livewire::test(FinanceApplicationShow::class, ['application' => $app->fresh()])
            ->assertSee(__('finance.download_pdf'), false)
            ->assertSee(route('finance.pdf', $app), false);
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
