<?php

use App\Services\DriveApplicationFormData;
use App\Services\DriveApplicationFormRenderer;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

$mapper = app(DriveApplicationFormData::class);
$renderer = app(DriveApplicationFormRenderer::class);
$mapper->ensureTemplatesExist();

$data = [
    'logo_name' => 'sara gamal',
    'financial_product_name' => 'Drive Loan 60m',
    'showroom_agent' => 'Cairo Showroom',
    'showroom' => 'Cairo Showroom',
    'date' => now()->format('d/m/Y'),
    'title' => 'Mr',
    'name_en' => 'Ahmed Mohamed Hassan',
    'name_ar' => 'أحمد محمد حسن',
    'name_combined' => 'أحمد محمد حسن',
    'dob' => '14/03/1988',
    'gender' => 'M',
    'nationality' => 'Egyptian',
    'id_type' => 'Egyptian',
    'id_number' => '28803141201234',
    'home_address' => '15 شارع 9، المعادي، القاهرة',
    'home_ownership' => 'Own',
    'home_duration' => '8 years',
    'phone_mobile' => '01001234567',
    'email' => '',
    'prev_address' => '22 شارع الهرم، الجيزة',
    'prev_ownership' => 'Old Rent',
    'prev_duration' => '3 years',
    'ref_name' => 'محمود سعيد',
    'ref_relation' => 'Brother',
    'ref_address' => '15 شارع 9، المعادي، القاهرة',
    'ref_phone' => '01119876543',
    'employment' => 'Self-Employed',
    'job_title' => 'صاحب منشأة',
    'job_duration' => '10 years',
    'company_name' => 'شركة النقل السريع',
    'business_type' => 'Commercial Registration',
    'work_address' => 'المنطقة الصناعية، السادس من أكتوبر',
    'office_phone' => '0233334444',
    'work_email' => '',
    'income_fixed' => '85,000',
    'income_variable' => '15,000',
    'income_total' => '100,000',
    'vehicles' => [
        ['brand' => 'Volvo', 'name' => 'FH16', 'model' => 'FH', 'year' => '2025', 'type' => 'Heavy Truck'],
        ['brand' => 'Mercedes', 'name' => 'Actros 1848', 'model' => 'Actros', 'year' => '2024', 'type' => 'Heavy Truck'],
        ['brand' => 'MAN', 'name' => 'TGX 18.480', 'model' => 'TGX', 'year' => '2025', 'type' => 'Heavy Truck'],
    ],
    'brand' => 'Volvo',
    'name' => 'FH16',
    'model' => 'FH',
    'year' => '2025',
    'color' => '',
    'engine_cc' => '',
    'options' => '',
    'price' => '2,850,000',
    'down_payment' => '750,000',
    'tenor_years' => '5',
    'docs_id' => true,
    'docs_residence' => true,
    'comments' => 'Lead LD-20260816-000042',
    'sales_officer' => 'Omar Khaled',
];

$tempDirectory = storage_path('app/mpdf-temp');
File::ensureDirectoryExists($tempDirectory);

$pdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => $tempDirectory,
    'default_font' => 'dejavusans',
    'directionality' => 'rtl',
    'autoScriptToLang' => true,
    'autoLangToFont' => true,
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
]);

$renderer->writeApplicantPage($pdf, $data);
$renderer->writeEmploymentPage($pdf, $data);

$outputDirectory = storage_path('app/samples');
File::ensureDirectoryExists($outputDirectory);
$outputPath = $outputDirectory.DIRECTORY_SEPARATOR.'drive-form-sample.pdf';
File::put($outputPath, $pdf->Output('', Destination::STRING_RETURN));

echo $outputPath.PHP_EOL;
