<?php

namespace App\Services;

use App\Models\ApplicationSetting;

class DrivePdfSettings
{
    public const LOGO_NAME = 'drive_pdf.logo_name';

    public const SHOWROOM_AGENT = 'drive_pdf.showroom_agent';

    public const SALES_OFFICER = 'drive_pdf.sales_officer';

    /** @return array{logo_name:string,showroom_agent:string,sales_officer:string} */
    public function values(): array
    {
        $stored = ApplicationSetting::query()
            ->whereIn('key', [
                self::LOGO_NAME,
                self::SHOWROOM_AGENT,
                self::SALES_OFFICER,
            ])
            ->pluck('value', 'key');

        return [
            'logo_name' => (string) ($stored[self::LOGO_NAME]
                ?? config('truckfund.drive_form_logo_name', 'sara gamal')),
            'showroom_agent' => (string) ($stored[self::SHOWROOM_AGENT]
                ?? config('truckfund.drive_form_showroom_agent', '')),
            'sales_officer' => (string) ($stored[self::SALES_OFFICER]
                ?? config('truckfund.drive_form_sales_officer', '')),
        ];
    }

    /** @param array{logo_name:string,showroom_agent:string,sales_officer:string} $values */
    public function save(array $values): void
    {
        foreach ([
            self::LOGO_NAME => $values['logo_name'],
            self::SHOWROOM_AGENT => $values['showroom_agent'],
            self::SALES_OFFICER => $values['sales_officer'],
        ] as $key => $value) {
            ApplicationSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => trim($value)],
            );
        }
    }
}
