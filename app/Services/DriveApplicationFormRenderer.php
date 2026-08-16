<?php

namespace App\Services;

use Mpdf\Mpdf;

/**
 * Overlay CRM values onto the scanned DRIVE application pages.
 *
 * Coordinates were calibrated against the blank JPG templates and are expressed
 * as percentages of the page so the overlay stays aligned when fitted to A4.
 */
class DriveApplicationFormRenderer
{
    public function __construct(
        protected DriveApplicationFormData $mapper,
    ) {}

    /** @param array<string, mixed> $data */
    public function writeApplicantPage(Mpdf $pdf, array $data): void
    {
        $this->addBackgroundPage($pdf, $this->mapper->applicantTemplate());

        $this->text($pdf, 21.1, 12.9, (string) $data['showroom'], 42);
        $this->text($pdf, 60.0, 12.9, (string) $data['date'], 22);

        $this->markChoice($pdf, $data['title'], [
            'Mr' => [21.2, 22.0],
            'Dr' => [31.5, 22.1],
            'Eng' => [41.9, 22.0],
            'Mrs' => [52.0, 22.3],
            'Ms' => [61.8, 22.2],
            'Other' => [71.9, 22.3],
        ]);

        $this->text($pdf, 21.1, 27.8, (string) $data['name_combined'], 58, 9);
        $this->text($pdf, 37.0, 31.8, (string) $data['dob'], 22);
        $this->markChoice($pdf, $data['gender'], [
            'M' => [20.9, 31.3],
            'F' => [28.4, 31.4],
        ]);
        $this->markChoice($pdf, $data['nationality'], [
            'Egyptian' => [20.8, 35.4],
            'Other' => [28.4, 35.5],
        ]);
        $this->markChoice($pdf, $data['id_type'], [
            'Egyptian' => [20.8, 39.9],
            'Other' => [28.4, 39.9],
        ]);
        $this->text($pdf, 37.0, 40.4, (string) $data['id_number'], 22);

        $this->text($pdf, 21.1, 48.5, (string) $data['home_address'], 55, 8.5);
        $this->markChoice($pdf, $data['home_ownership'], [
            'Own' => [20.7, 51.8],
            'New Rent' => [34.5, 51.9],
            'Old Rent' => [49.9, 52.1],
            'With Parents' => [65.5, 52.0],
        ]);
        $this->text($pdf, 21.1, 57.3, (string) $data['home_duration'], 55);
        $this->text($pdf, 21.1, 59.9, (string) $data['phone_mobile'], 55);
        // Email intentionally skipped.
        $this->text($pdf, 21.1, 66.0, (string) $data['prev_address'], 55, 8);
        $this->markChoice($pdf, $data['prev_ownership'], [
            'Own' => [20.4, 68.5],
            'New Rent' => [34.2, 68.7],
            'Old Rent' => [49.8, 68.5],
            'With Parents' => [65.4, 68.7],
        ]);
        $this->text($pdf, 21.1, 74.0, (string) $data['prev_duration'], 55);

        $this->text($pdf, 21.1, 84.1, (string) $data['ref_name'], 55);
        $this->text($pdf, 21.1, 87.6, (string) $data['ref_relation'], 55);
        $this->text($pdf, 21.1, 91.4, (string) $data['ref_address'], 55, 8);
        $this->text($pdf, 21.1, 95.5, (string) $data['ref_phone'], 55);
    }

    /** @param array<string, mixed> $data */
    public function writeEmploymentPage(Mpdf $pdf, array $data): void
    {
        $this->addBackgroundPage($pdf, $this->mapper->employmentTemplate());

        $this->markChoice($pdf, $data['employment'], [
            'Salaried' => [27.6, 6.5],
            'Self-Employed' => [37.3, 6.4],
        ]);

        $this->text($pdf, 27.1, 10.4, (string) $data['job_title'], 42, 9);
        $this->text($pdf, 27.1, 13.5, (string) $data['job_duration'], 42);
        $this->text($pdf, 27.1, 16.7, (string) $data['company_name'], 42, 8.5);
        $this->text($pdf, 27.1, 20.3, (string) $data['business_type'], 42, 8.5);
        $this->text($pdf, 27.1, 25.2, (string) $data['work_address'], 42, 7.5);
        $this->text($pdf, 27.1, 29.9, (string) $data['office_phone'], 42);
        // Work email intentionally skipped.

        $this->text($pdf, 42.5, 40.6, (string) $data['income_fixed'], 16);
        $this->text($pdf, 42.5, 42.9, (string) $data['income_variable'], 16);
        $this->text($pdf, 42.5, 45.3, (string) $data['income_total'], 16);

        $this->text($pdf, 27.1, 50.8, (string) $data['brand'], 42);
        $this->text($pdf, 27.1, 53.1, (string) $data['model'], 42);
        $this->text($pdf, 27.1, 55.5, (string) $data['year'], 42);
        $this->text($pdf, 27.1, 57.4, (string) $data['color'], 42, 8.5);
        $this->text($pdf, 27.1, 59.6, (string) $data['engine_cc'], 42);
        $this->text($pdf, 27.1, 61.9, (string) $data['options'], 42, 8);
        $this->text($pdf, 27.1, 64.0, (string) $data['price'], 42);
        $this->text($pdf, 27.1, 66.2, (string) $data['down_payment'], 42);
        $this->text($pdf, 27.1, 68.3, (string) $data['tenor_years'], 42);

        if (! empty($data['docs_residence'])) {
            $this->mark($pdf, 21.3, 88.2);
        }
        if (! empty($data['docs_id'])) {
            $this->mark($pdf, 52.3, 88.2);
        }

        $this->text($pdf, 22.2, 91.5, (string) $data['comments'], 68, 8);
        $this->text($pdf, 69.0, 97.8, (string) $data['sales_officer'], 26, 8.5);
    }

    protected function addBackgroundPage(Mpdf $pdf, string $imagePath): void
    {
        $pdf->AddPageByArray([
            'orientation' => 'P',
            'mgl' => 0,
            'mgr' => 0,
            'mgt' => 0,
            'mgb' => 0,
            'mgh' => 0,
            'mgf' => 0,
        ]);

        // Stretch the blank scan to fill the printable page.
        $pdf->Image($imagePath, 0, 0, 210, 297, '', '', true, false);
    }

    protected function text(
        Mpdf $pdf,
        float $xPercent,
        float $yPercent,
        string $value,
        float $maxWidthPercent,
        float $fontSize = 9,
    ): void {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $x = 210 * ($xPercent / 100);
        $y = 297 * ($yPercent / 100);
        $width = 210 * ($maxWidthPercent / 100);

        $pdf->SetFont('dejavusans', '', $fontSize);
        $pdf->SetTextColor(15, 25, 80);
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($width, $fontSize * 0.45, $value, 0, 'L');
    }

    /** @param array<string, array{0:float,1:float}> $choices */
    protected function markChoice(Mpdf $pdf, mixed $selected, array $choices): void
    {
        if ($selected === null || $selected === '') {
            return;
        }

        $key = (string) $selected;
        if (! isset($choices[$key])) {
            return;
        }

        [$xPercent, $yPercent] = $choices[$key];
        $this->mark($pdf, $xPercent, $yPercent);
    }

    protected function mark(Mpdf $pdf, float $xPercent, float $yPercent): void
    {
        $x = 210 * ($xPercent / 100);
        $y = 297 * ($yPercent / 100);
        $size = 2.2;

        $pdf->SetDrawColor(15, 25, 80);
        $pdf->SetLineWidth(0.45);
        $pdf->Line($x - $size, $y - $size, $x + $size, $y + $size);
        $pdf->Line($x - $size, $y + $size, $x + $size, $y - $size);
    }
}
