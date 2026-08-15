<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('truckfund.seed_admins', []) as $admin) {
            User::query()->updateOrCreate(
                ['email' => $admin['email']],
                [
                    'full_name' => $admin['full_name'],
                    'role' => UserRole::Admin,
                    'password' => Hash::make($admin['password']),
                    'reports_to_user_id' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
