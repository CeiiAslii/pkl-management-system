<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => Role::Student,
            'status' => AccountStatus::Pending,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin, 'status' => AccountStatus::Active]);
    }

    public function teacher(): static
    {
        return $this->state(fn () => ['role' => Role::Teacher, 'status' => AccountStatus::Active]);
    }

    public function approvedStudent(): static
    {
        return $this->state(fn () => [
            'role' => Role::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'approved_by' => User::factory()->admin(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => AccountStatus::Suspended]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
