<?php

namespace Tests\Feature;

use App\Enums\DocType;
use App\Models\ApplicationDocument;
use App\Models\AutoProduct;
use App\Models\Customer;
use App\Models\FinancialProduct;
use App\Models\Identification;
use App\Models\Merchant;
use App\Models\User;
use App\Services\DriveApplicationFormData;
use App\Services\FinanceApplicationPdfService;
use App\Services\FinanceApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use setasign\Fpdi\PdfParser\StreamReader;
use Tests\TestCase;

class FinanceApplicationPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_download_requires_complete_financial_details(): void
    {
        $user = User::factory()->financeOfficer()->create();
        $customer = Customer::query()->create([
            'display_name' => 'PDF Customer',
            'mobile_number' => '01001112233',
        ]);

        $app = app(FinanceApplicationService::class)->createDraft([
            'customer_id' => $customer->customer_id,
            'user_id' => $user->user_id,
            'down_payment' => 0,
            'total_loan_amount' => 0,
            'total_truck_price' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('finance.pdf', $app))
            ->assertStatus(422);
    }

    public function test_pdf_download_returns_pdf_for_complete_application(): void
    {
        Storage::fake('documents');

        $user = User::factory()->financeOfficer()->create();
        $merchant = Merchant::query()->create([
            'name' => 'Test Bank',
            'type' => 1,
            'is_active' => true,
        ]);
        $autoProduct = AutoProduct::query()->create([
            'brand' => 'Volvo',
            'name' => 'FH16',
            'type' => 1,
            'chassis' => 'CH-PDF-001',
            'price' => 600000,
            'is_active' => true,
        ]);
        $financialProduct = FinancialProduct::query()->create([
            'name' => 'Truck Loan 60m',
            'product_code' => 'PDF-LOAN-01',
            'percentage' => 18.5,
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'display_name' => 'PDF Customer',
            'mobile_number' => '01001112233',
            'city' => 'القاهرة',
            'area' => 'المعادي',
            'address' => '15 شارع 9',
        ]);

        // Use the real DRIVE template JPG so Image() embeds a non-trivial photo.
        $photo = file_get_contents(resource_path('pdf-forms/drive-application-applicant.jpg'));
        Storage::disk('documents')->put('customers/id-front.jpg', $photo);
        Identification::query()->create([
            'customer_id' => $customer->customer_id,
            'id_type' => 1,
            'id_number' => '12345678901234',
            'name_en' => 'PDF Customer',
            'name_ar' => 'عميل تجريبي',
            'id_front_url' => 'customers/id-front.jpg',
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
            'monthly_income' => 25000,
        ]);

        $attachmentPdf = new Mpdf(['tempDir' => storage_path('framework/cache')]);
        $attachmentPdf->WriteHTML('First attachment page<pagebreak />Second attachment page');
        Storage::disk('documents')->put(
            'applications/supporting-document.pdf',
            $attachmentPdf->Output('', Destination::STRING_RETURN),
        );
        ApplicationDocument::query()->create([
            'app_id' => $app->app_id,
            'doc_type' => DocType::Other,
            'file_url' => 'applications/supporting-document.pdf',
            'uploaded_at' => now(),
            'uploaded_by' => $user->user_id,
        ]);

        $pdfService = app(FinanceApplicationPdfService::class);
        $this->assertTrue($pdfService->canGenerate($app));

        $loaded = $pdfService->loadForPdf($app->app_id);
        $mapped = app(DriveApplicationFormData::class)->fromApplication($loaded);
        $this->assertStringContainsString('15 شارع 9', $mapped['home_address']);
        $this->assertStringContainsString('المعادي', $mapped['home_address']);
        $this->assertStringContainsString('القاهرة', $mapped['home_address']);

        $attachments = $pdfService->collectAttachments($loaded);
        $this->assertTrue($attachments->contains(fn (array $a) => $a['type'] === 'image'));
        $this->assertNotNull($attachments->firstWhere('type', 'image')['absolute_path']);

        $response = $this->actingAs($user)->get(route('finance.pdf', $app));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $generatedPdf = new Mpdf(['tempDir' => storage_path('framework/cache')]);
        $pageCount = $generatedPdf->setSourceFile(
            StreamReader::createByString($response->getContent()),
        );

        // 2 form pages + 1 image page + 2 pages from the attached PDF.
        $this->assertGreaterThanOrEqual(5, $pageCount);
        $this->assertGreaterThan(200000, strlen($response->getContent()));
    }

    public function test_legacy_document_path_prefixes_still_resolve(): void
    {
        Storage::fake('documents');
        Storage::disk('documents')->put('customers/old-id.jpg', 'fake-image');

        $service = app(FinanceApplicationPdfService::class);
        $method = new \ReflectionMethod($service, 'attachmentEntry');
        $method->setAccessible(true);

        $entry = $method->invoke($service, 'ID', 'documents/customers/old-id.jpg');

        $this->assertSame('image', $entry['type']);
        $this->assertSame('customers/old-id.jpg', $entry['path']);
        $this->assertNotNull($entry['absolute_path']);
    }
}
