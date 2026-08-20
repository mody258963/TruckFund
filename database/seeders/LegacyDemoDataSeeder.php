<?php

namespace Database\Seeders;

use App\Models\AutoProduct;
use App\Models\FinancialProduct;
use App\Models\Freelancer;
use App\Models\Lead;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Removes the sample records shipped by earlier demo seeders so a seeded
 * install contains nothing beyond the two admins and the truck catalog.
 * Only records created by those seeders are matched, never real data.
 */
class LegacyDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        Lead::query()->where('email', 'ahmed@example.com')->delete();
        Freelancer::query()->where('phone', '01009998877')->delete();

        Merchant::query()->where('contact_email', 'partner@example.com')->delete();
        FinancialProduct::query()->where('product_code', 'STL-01')->delete();
        AutoProduct::query()->where('chassis', 'CH-001')->delete();

        User::query()->whereIn('email', [
            'admin@truckfund.test',
            'sales@truckfund.test',
            'finance@truckfund.test',
            'aiman@truckfund.test',
            'mohammed@truckfund.test',
            'admin@TestFund.test',
            'manager@TestFund.test',
            'tl@TestFund.test',
            'sales@TestFund.test',
            'sales2@TestFund.test',
            'finance@TestFund.test',
            'admin@ashmawyfund.test',
            'sales@ashmawyfund.test',
            'finance@ashmawyfund.test',
        ])->delete();
    }
}
