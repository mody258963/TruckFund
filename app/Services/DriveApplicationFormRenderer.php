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
    /** Every overlaid value is enlarged by this factor over the calibrated size. */
    protected const TEXT_SCALE = 1.25;

    /** Overlay values print in solid black so they stay legible over the grey scan. */
    protected const TEXT_COLOR = [0, 0, 0];

    /** Serif face with Arabic coverage; heavier on paper than DejaVu Sans. */
    protected const TEXT_FONT = 'freeserif';

    public function __construct(
        protected DriveApplicationFormData $mapper,
    ) {}

    /** @param array<string, mixed> $data */
    public function writeApplicantPage(Mpdf $pdf, array $data): void
    {
        $this->addBackgroundPage($pdf, $this->mapper->applicantTemplate());

        // Custom CRM name on the left of the DRIVE logo; financial product on the right.
        $this->headerText($pdf, 14.0, 7.2, (string) $data['logo_name'], 27);
        $this->headerText($pdf, 60.0, 7.2, (string) ($data['financial_product_name'] ?? ''), 34);
        // The original Showroom / Dealer line remains independently editable.
        $this->headerText($pdf, 21.1, 12.6, (string) $data['showroom_agent'], 42, 11);
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

        // Sit just under the "Home Address" label on the dotted line.
        $this->text($pdf, 21.1, 48.2, (string) $data['home_address'], 55, 9);
        $this->markChoice($pdf, $data['home_ownership'], [
            'Own' => [20.7, 51.8],
            'New Rent' => [34.5, 51.9],
            'Old Rent' => [49.9, 52.1],
            'With Parents' => [65.5, 52.0],
        ]);
        $this->text($pdf, 21.1, 57.3, (string) $data['home_duration'], 55);
        $this->text($pdf, 21.1, 59.9, (string) $data['phone_mobile'], 55);
        // Email intentionally skipped.
        $this->text($pdf, 21.1, 66.0, (string) $data['prev_address'], 55, 8.5);
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

        $this->writeVehicleColumns($pdf, $data['vehicles'] ?? []);

        // Deal totals stay on the original price / down-payment lines.
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
        $this->headerText($pdf, 66.0, 97.1, (string) $data['sales_officer'], 30, 11);
    }

    /**
     * Draw up to three vehicles as compact side-by-side columns across the
     * English and Arabic halves of the "Requested Car" section.
     *
     * @param  list<array{brand?:string,name?:string,model?:string,year?:string}>  $vehicles
     */
    protected function writeVehicleColumns(Mpdf $pdf, array $vehicles): void
    {
        $vehicles = array_values(array_slice($vehicles, 0, 3));
        if ($vehicles === []) {
            return;
        }

        $count = count($vehicles);
        $startX = 22.0;
        $endX = 88.0;
        $gap = 1.5;
        $usable = $endX - $startX - ($gap * max(0, $count - 1));
        $columnWidth = $usable / $count;
        $fontSize = $count >= 3 ? 7.0 : ($count === 2 ? 7.5 : 8.5);

        $rows = [
            50.4 => 'brand',
            53.0 => 'name',
            55.6 => 'model',
            58.2 => 'year',
        ];

        foreach ($vehicles as $index => $vehicle) {
            $x = $startX + ($index * ($columnWidth + $gap));
            foreach ($rows as $y => $key) {
                $this->text($pdf, $x, $y, (string) ($vehicle[$key] ?? ''), $columnWidth, $fontSize);
            }
        }
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

    /** The CRM-managed names around the DRIVE logo are the largest text on the form. */
    protected function headerText(
        Mpdf $pdf,
        float $xPercent,
        float $yPercent,
        string $value,
        float $maxWidthPercent,
        float $fontSize = 13,
    ): void {
        $this->text($pdf, $xPercent, $yPercent, $value, $maxWidthPercent, $fontSize);
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
        $fontSize *= self::TEXT_SCALE;

        $pdf->SetFont(self::TEXT_FONT, 'B', $fontSize);
        $pdf->SetTextColor(...self::TEXT_COLOR);
        $pdf->SetXY($x, $y);
        // Line height must clear Arabic glyph descenders; too-tight cells clip
        // longer address lines so they look "missing" on the form.
        $pdf->MultiCell($width, max(4.2, $fontSize * 0.55), $value, 0, 'L');
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

        $pdf->SetDrawColor(...self::TEXT_COLOR);
        $pdf->SetLineWidth(0.55);
        $pdf->Line($x - $size, $y - $size, $x + $size, $y + $size);
        $pdf->Line($x - $size, $y + $size, $x + $size, $y - $size);
    }
}
