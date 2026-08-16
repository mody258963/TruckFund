<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\ApplicationSetting;
use App\Models\Customer;
use App\Models\FinancialProduct;
use App\Models\User;
use App\Services\DriveApplicationFormData;
use App\Services\DrivePdfSettings;
use App\Services\FinanceApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DrivePdfSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_all_drive_pdf_names_from_crm_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Settings::class)
            ->assertSet('drivePdf.logo_name', 'sara gamal')
            ->set('drivePdf.logo_name', 'Sara Gamal Updated')
            ->set('drivePdf.showroom_agent', 'Cairo Showroom Agent')
            ->set('drivePdf.sales_officer', 'Mona Sales Officer')
            ->call('saveDrivePdfSettings')
            ->assertHasNoErrors()
            ->assertSet('drivePdf.logo_name', 'Sara Gamal Updated')
            ->assertSet('drivePdf.showroom_agent', 'Cairo Showroom Agent')
            ->assertSet('drivePdf.sales_officer', 'Mona Sales Officer');

        $this->assertDatabaseHas('application_settings', [
            'key' => DrivePdfSettings::LOGO_NAME,
            'value' => 'Sara Gamal Updated',
        ]);
        $this->assertDatabaseHas('application_settings', [
            'key' => DrivePdfSettings::SHOWROOM_AGENT,
            'value' => 'Cairo Showroom Agent',
        ]);
        $this->assertDatabaseHas('application_settings', [
            'key' => DrivePdfSettings::SALES_OFFICER,
            'value' => 'Mona Sales Officer',
        ]);
    }

    public function test_drive_pdf_mapper_uses_saved_names(): void
    {
        $user = User::factory()->financeOfficer()->create([
            'full_name' => 'Assigned Officer',
        ]);
        $customer = Customer::query()->create([
            'display_name' => 'PDF Settings Customer',
            'mobile_number' => '01001110000',
        ]);
        $financialProduct = FinancialProduct::query()->create([
            'name' => 'Drive Loan 60m',
            'product_code' => 'DRIVE-60',
            'percentage' => 18.5,
            'is_active' => true,
        ]);
        $application = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'financial_product_id' => $financialProduct->product_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);

        app(DrivePdfSettings::class)->save([
            'logo_name' => 'Logo Side Name',
            'showroom_agent' => 'Alex Showroom',
            'sales_officer' => 'CRM Sales Name',
        ]);

        $mapped = app(DriveApplicationFormData::class)->fromApplication(
            $application->load([
                'customer.references',
                'customer.financialData',
                'autoProduct',
                'autoProducts',
                'financialProduct',
                'user',
            ]),
        );

        $this->assertSame('Logo Side Name', $mapped['logo_name']);
        $this->assertSame('Drive Loan 60m', $mapped['financial_product_name']);
        $this->assertSame('Alex Showroom', $mapped['showroom_agent']);
        $this->assertSame('Alex Showroom', $mapped['showroom']);
        $this->assertSame('CRM Sales Name', $mapped['sales_officer']);
    }

    public function test_blank_sales_officer_setting_falls_back_to_application_user(): void
    {
        $user = User::factory()->financeOfficer()->create([
            'full_name' => 'Assigned Officer',
        ]);
        $customer = Customer::query()->create([
            'display_name' => 'Fallback Customer',
            'mobile_number' => '01001110001',
        ]);
        $application = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);

        ApplicationSetting::query()->create([
            'key' => DrivePdfSettings::SALES_OFFICER,
            'value' => '',
        ]);

        $mapped = app(DriveApplicationFormData::class)->fromApplication(
            $application->load(['customer.references', 'customer.financialData', 'autoProduct', 'autoProducts', 'user']),
        );

        $this->assertSame('Assigned Officer', $mapped['sales_officer']);
    }
}
