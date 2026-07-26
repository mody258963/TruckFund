<?php

namespace Database\Seeders;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\AutoProduct;
use App\Models\FinancialProduct;
use App\Models\Freelancer;
use App\Models\Lead;
use App\Models\Merchant;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedCatalog();
        $this->seedFreelancer();
        $this->seedSampleLead();
    }

    private function seedUsers(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@testfund.test'],
            [
                'full_name' => 'Admin User',
                'role' => UserRole::Admin,
                'password' => Hash::make('password'),
                'is_active' => true,
                'reports_to_user_id' => null,
            ]
        );

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@testfund.test'],
            [
                'full_name' => 'CRM Manager',
                'role' => UserRole::Manager,
                'password' => Hash::make('password'),
                'is_active' => true,
                'reports_to_user_id' => null,
            ]
        );

        $tl = User::query()->updateOrCreate(
            ['email' => 'tl@testfund.test'],
            [
                'full_name' => 'Team Leader',
                'role' => UserRole::TeamLeader,
                'password' => Hash::make('password'),
                'is_active' => true,
                'reports_to_user_id' => $manager->user_id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'sales@testfund.test'],
            [
                'full_name' => 'Sales One',
                'role' => UserRole::SalesAgent,
                'password' => Hash::make('password'),
                'is_active' => true,
                'reports_to_user_id' => $tl->user_id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'sales2@testfund.test'],
            [
                'full_name' => 'Sales Two',
                'role' => UserRole::SalesAgent,
                'password' => Hash::make('password'),
                'is_active' => true,
                'reports_to_user_id' => $tl->user_id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'finance@testfund.test'],
            [
                'full_name' => 'Finance Officer',
                'role' => UserRole::FinanceOfficer,
                'password' => Hash::make('password'),
                'is_active' => true,
                'reports_to_user_id' => null,
            ]
        );
    }

    private function seedCatalog(): void
    {
        Merchant::query()->firstOrCreate(
            ['contact_email' => 'partner@example.com'],
            [
                'name' => 'Nile Finance Partner',
                'type' => 1,
                'is_active' => true,
            ]
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
    }

    private function seedFreelancer(): void
    {
        $admin = User::query()->where('email', 'admin@testfund.test')->first();

        Freelancer::query()->firstOrCreate(
            ['phone' => '01009998877'],
            [
                'full_name' => 'Referrer Ahmed',
                'national_id' => '29901011234567',
                'is_locked' => true,
                'created_by_user_id' => $admin?->user_id,
            ]
        );
    }

    private function seedSampleLead(): void
    {
        if (Lead::query()->where('email', 'ahmed@example.com')->exists()) {
            return;
        }

        $sales = User::query()->where('email', 'sales@testfund.test')->first();
        $admin = User::query()->where('email', 'admin@testfund.test')->first();
        $freelancer = Freelancer::query()->first();

        $leadService = app(LeadService::class);
        $lead = $leadService->create([
            'customer_name' => 'Ahmed Hassan',
            'phone' => '01001234567',
            'email' => 'ahmed@example.com',
            'car_brand' => 'Mercedes',
            'price' => 900000,
            'down_payment_pct' => 25,
            'assigned_user_id' => $sales?->user_id,
            'freelancer_id' => $freelancer?->freelancer_id,
        ], $admin, LeadSource::Referral);

        $lead->update(['status' => LeadStatus::Pending]);
    }
}
