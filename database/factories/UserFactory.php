<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        $id = Str::lower(Str::random(8));

        return [
            'full_name' => 'Test User '.$id,
            'email' => "user-{$id}@example.test",
            'phone' => '0100'.random_int(1000000, 9999999),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::SalesAgent,
            'is_active' => true,
            'id_card_url' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function financeOfficer(): static
    {
        return $this->state(fn () => ['role' => UserRole::FinanceOfficer]);
    }
}
