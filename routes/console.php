<?php

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\AutoProduct;
use App\Models\FinancialProduct;
use App\Models\Lead;
use App\Models\Merchant;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('truckfund:seed-demo', function () {
    $users = [
        ['full_name' => 'Admin User', 'email' => 'admin@truckfund.test', 'role' => UserRole::Admin],
        ['full_name' => 'Sales Agent', 'email' => 'sales@truckfund.test', 'role' => UserRole::SalesAgent],
        ['full_name' => 'Finance Officer', 'email' => 'finance@truckfund.test', 'role' => UserRole::FinanceOfficer],
    ];

    foreach ($users as $data) {
        User::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'full_name' => $data['full_name'],
                'role' => $data['role'],
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
    }

    Merchant::query()->firstOrCreate(
        ['contact_email' => 'partner@example.com'],
        ['name' => 'Nile Finance Partner', 'type' => 1, 'is_active' => true]
    );

    FinancialProduct::query()->firstOrCreate(
        ['product_code' => 'STL-01'],
        [
            'name' => 'Standard Truck Loan',
            'percentage' => 18.5,
            'description' => 'Default truck financing product',
            'is_active' => true,
        ]
    );

    AutoProduct::query()->firstOrCreate(
        ['chassis' => 'CH-001'],
        [
            'name' => 'Heavy Duty 6x4',
            'type' => 1,
            'brand' => 'Mercedes',
            'price' => 850000,
            'is_active' => true,
        ]
    );

    if (! Lead::query()->where('email', 'ahmed@example.com')->exists()) {
        app(LeadService::class)->create([
            'customer_name' => 'Ahmed Hassan',
            'phone' => '01001234567',
            'email' => 'ahmed@example.com',
            'car_brand' => 'Mercedes',
            'price' => 900000,
            'down_payment_pct' => 25,
        ]);
        Lead::query()->where('email', 'ahmed@example.com')->first()?->update(['status' => LeadStatus::Pending]);
    }

    $this->info('Demo data seeded.');
    $this->line('  admin@truckfund.test / password');
    $this->line('  sales@truckfund.test / password');
    $this->line('  finance@truckfund.test / password');
})->purpose('Seed demo users and catalog (no Faker)');
