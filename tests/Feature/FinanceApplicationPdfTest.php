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
            'city' => 'Cairo',
            'address' => '123 Main St',
        ]);

        Storage::disk('documents')->put('customers/id-front.png', $this->largePngBytes());
        Identification::query()->create([
            'customer_id' => $customer->customer_id,
            'id_type' => 1,
            'id_number' => '12345678901234',
            'name_en' => 'PDF Customer',
            'id_front_url' => 'customers/id-front.png',
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

        $response = $this->actingAs($user)->get(route('finance.pdf', $app));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $generatedPdf = new Mpdf(['tempDir' => storage_path('framework/cache')]);
        $pageCount = $generatedPdf->setSourceFile(
            StreamReader::createByString($response->getContent()),
        );

        $this->assertGreaterThanOrEqual(5, $pageCount);
    }

    /**
     * A truecolor PNG of random noise, built without ext-gd. Noise compresses
     * poorly, so the file exceeds pcre.backtrack_limit once base64 encoded.
     */
    private function largePngBytes(): string
    {
        $width = 700;
        $height = 700;

        $raw = '';
        for ($row = 0; $row < $height; $row++) {
            $raw .= "\x00".random_bytes($width * 3);
        }

        $header = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

        $bytes = "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', $header)
            .$this->pngChunk('IDAT', (string) gzcompress($raw))
            .$this->pngChunk('IEND', '');

        $this->assertGreaterThan(1000000, strlen(base64_encode($bytes)));

        return $bytes;
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
