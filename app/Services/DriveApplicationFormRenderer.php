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
    protected const TEXT_SCALE = 2.0;

    /** Millimetres each value is re-stamped sideways to fake a heavier weight. */
    protected const BOLD_SMEAR = 0.18;

    /** Long values shrink instead of wrapping once they exceed their field width. */
    protected const MIN_FONT_SIZE = 7.5;

    /** Overlay values print in solid black so they stay legible over the grey scan. */
    protected const TEXT_COLOR = [0, 0, 0];

    /** Serif face with Arabic coverage; heavier on paper than DejaVu Sans. */
    protected const TEXT_FONT = 'freeserif';

    /**
     * Values and marks are nudged up by this share of the page height; the
     * enlarged font otherwise sits low against the printed rule of each field.
     */
    protected const Y_OFFSET = -1.1;

    public function __construct(
        protected DriveApplicationFormData $mapper,
    ) {}

    /** @param array<string, mixed> $data */
    public function writeApplicantPage(Mpdf $pdf, array $data): void
    {
        $this->addBackgroundPage($pdf, $this->mapper->applicantTemplate());

        // Custom CRM name on the left of the DRIVE logo; financial product on the right.
        $this->headerText($pdf, 14.0, 2.6, (string) $data['logo_name'], 27);
        $this->headerText($pdf, 60.0, 2.6, (string) ($data['financial_product_name'] ?? ''), 34);
        // The original Showroom / Dealer line remains independently editable.
        $this->headerText($pdf, 21.1, 12.6, (string) $data['showroom_agent'], 42, 11);
        // Spaced digits sit on the dotted day / month / year boxes.
        $this->text($pdf, 58.5, 12.2, (string) $data['date'], 30, 12);
        // Applicant name is the headline field on the form, so it prints largest,
        // centred across the full width of its line.
        $this->text($pdf, 21.1, 26.5, (string) $data['name_combined'], 58, 13, 'C');
        $this->text($pdf, 37.0, 31.4, (string) $data['dob'], 30, 11);
        // A 14-digit national ID needs more room than the other short fields.
        $this->text($pdf, 37.0, 40.4, (string) $data['id_number'], 30);

        // Sit just under the "Home Address" label on the dotted line.
        $this->text($pdf, 21.1, 48.2, (string) $data['home_address'], 55, 9);
        $this->text($pdf, 21.1, 57.3, (string) $data['home_duration'], 55);
        $this->text($pdf, 21.1, 59.9, (string) $data['phone_mobile'], 55);
        // Email intentionally skipped.
        $this->text($pdf, 21.1, 66.0, (string) $data['prev_address'], 55, 8.5);
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

        // Same financial product as page 1, fixed in the top-right corner.
        $this->headerText($pdf, 60.0, 1.4, (string) ($data['financial_product_name'] ?? ''), 34);

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
        $this->text($pdf, 27.1, 63.2, (string) $data['price'], 42);
        $this->text($pdf, 27.1, 65.4, (string) $data['down_payment'], 42);
        $this->text($pdf, 27.1, 68.3, (string) $data['tenor_years'], 42);

        $this->text($pdf, 22.2, 91.5, (string) $data['comments'], 68, 8);
        // Sit against the right edge of the sales-officer line.
        $this->headerText($pdf, 58.0, 97.1, (string) $data['sales_officer'], 36, 11, 'R');
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

        // Pairs, not keys: PHP truncates float array keys to integers.
        $rows = [
            [50.4, 'brand'],
            [53.0, 'name'],
            [55.6, 'model'],
            [58.2, 'year'],
        ];

        foreach ($vehicles as $index => $vehicle) {
            $x = $startX + ($index * ($columnWidth + $gap));
            foreach ($rows as [$y, $key]) {
                $this->text($pdf, $x, $y, (string) ($vehicle[$key] ?? ''), $columnWidth, $fontSize, 'C');
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

        // Overlaid values sit near the page edge; without this a bottom-row value
        // would trigger an auto page break and add a blank page to the document.
        $pdf->SetAutoPageBreak(false);

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
        string $align = 'L',
    ): void {
        $this->text($pdf, $xPercent, $yPercent, $value, $maxWidthPercent, $fontSize, $align);
    }

    protected function text(
        Mpdf $pdf,
        float $xPercent,
        float $yPercent,
        string $value,
        float $maxWidthPercent,
        float $fontSize = 9,
        string $align = 'L',
    ): void {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $x = 210 * ($xPercent / 100);
        $y = 297 * (($yPercent + self::Y_OFFSET) / 100);
        $width = 210 * ($maxWidthPercent / 100);
        $fontSize *= self::TEXT_SCALE;

        // MultiCell adds its own padding plus the smear offset, so the text has to
        // clear a slightly narrower box than the cell to stay on one line.
        $usableWidth = $width - self::BOLD_SMEAR - 2.0;

        $pdf->SetFont(self::TEXT_FONT, 'B', $fontSize);
        // Shrink rather than wrap: a wrapped line would spill onto the field below.
        while ($fontSize > self::MIN_FONT_SIZE && $pdf->GetStringWidth($value) > $usableWidth) {
            $fontSize = max(self::MIN_FONT_SIZE, $fontSize - 0.25);
            $pdf->SetFont(self::TEXT_FONT, 'B', $fontSize);
        }

        $pdf->SetTextColor(...self::TEXT_COLOR);

        // Line height must clear Arabic glyph descenders; too-tight cells clip
        // longer address lines so they look "missing" on the form.
        $lineHeight = max(4.2, $fontSize * 0.55);

        // The bold face alone still looks thin against the scan, so each value is
        // stamped a second time a hair to the side to thicken every stroke.
        foreach ([0.0, self::BOLD_SMEAR] as $offset) {
            $pdf->SetXY($x + $offset, $y);
            $pdf->MultiCell($width, $lineHeight, $value, 0, $align);
        }
    }

}
