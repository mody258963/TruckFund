<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\AutoProduct;
use App\Models\FinancialProduct;
use App\Models\Lead;
use App\Models\Merchant;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'full_name' => 'Admin User',
            'email' => 'admin@truckfund.test',
            'password' => 'password',
        ]);

        User::factory()->create([
            'full_name' => 'Sales Agent',
            'email' => 'sales@truckfund.test',
            'role' => UserRole::SalesAgent,
            'password' => 'password',
        ]);

        User::factory()->financeOfficer()->create([
            'full_name' => 'Finance Officer',
            'email' => 'finance@truckfund.test',
            'password' => 'password',
        ]);

        Merchant::query()->create([
            'name' => 'Nile Finance Partner',
            'type' => 1,
            'contact_email' => 'partner@example.com',
            'is_active' => true,
        ]);

        FinancialProduct::query()->create([
            'name' => 'Standard Truck Loan',
            'product_code' => 'STL-01',
            'percentage' => 18.5,
            'description' => 'Default truck financing product',
            'is_active' => true,
        ]);

        AutoProduct::query()->create([
            'name' => 'Heavy Duty 6x4',
            'type' => 1,
            'brand' => 'Mercedes',
            'chassis' => 'CH-001',
            'price' => 850000,
            'is_active' => true,
        ]);

        $leadService = app(LeadService::class);
        $leadService->create([
            'customer_name' => 'Ahmed Hassan',
            'phone' => '01001234567',
            'email' => 'ahmed@example.com',
            'car_brand' => 'Mercedes',
            'price' => 900000,
            'down_payment_pct' => 25,
        ]);

        Lead::query()->first()?->update(['status' => LeadStatus::Pending]);
    }
}
