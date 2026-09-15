<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => '080000000000',
            'address' => fake()->address(),
            'pkl_place_name' => fake()->company(),
            'pkl_latitude' => null,
            'pkl_longitude' => null,
            'pkl_location_accuracy' => null,
            'pkl_contact_name' => fake()->name(),
            'pkl_contact_phone' => '080000000000',
        ];
    }
}
