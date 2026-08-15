<?php

namespace Tests\Feature;

use App\Enums\AutoProductType;
use App\Enums\UserRole;
use App\Models\AutoProduct;
use App\Models\FinancialProduct;
use App\Models\Freelancer;
use App\Models\Lead;
use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_only_the_two_admins(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expected = collect(config('truckfund.seed_admins'));

        $this->assertSame(
            $expected->pluck('email')->sort()->values()->all(),
            User::query()->orderBy('email')->pluck('email')->all(),
        );

        foreach ($expected as $admin) {
            $user = User::query()->where('email', $admin['email'])->sole();

            $this->assertSame($admin['full_name'], $user->full_name);
            $this->assertSame(UserRole::Admin, $user->role);
            $this->assertTrue(Hash::check($admin['password'], $user->password));
        }
    }

    public function test_seeded_admin_passwords_are_strong(): void
    {
        foreach (config('truckfund.seed_admins') as $admin) {
            $password = $admin['password'];

            $this->assertGreaterThanOrEqual(16, strlen($password));
            $this->assertMatchesRegularExpression('/[a-z]/', $password);
            $this->assertMatchesRegularExpression('/[A-Z]/', $password);
            $this->assertMatchesRegularExpression('/\d/', $password);
            $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $password);
        }
    }

    public function test_it_seeds_the_heavy_truck_catalog_and_nothing_else(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(30, AutoProduct::query()->count());
        $this->assertSame(
            count(AutoProductType::cases()),
            AutoProduct::query()->distinct()->count('type'),
        );
        $this->assertFalse(AutoProduct::query()->whereNull('model_year')->exists());
        $this->assertGreaterThanOrEqual(10, AutoProduct::query()->distinct()->count('brand'));

        $this->assertSame(0, Merchant::query()->count());
        $this->assertSame(0, FinancialProduct::query()->count());
        $this->assertSame(0, Freelancer::query()->count());
        $this->assertSame(0, Lead::query()->count());
    }
}
