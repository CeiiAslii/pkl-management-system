<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_profile_id' => StudentProfile::factory()->for(User::factory()->approvedStudent()),
            'requested_for' => fake()->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'type' => fake()->randomElement(['izin', 'sakit']),
            'reason' => fake()->paragraph(),
            'supporting_file_path' => null,
            'status' => 'pending',
        ];
    }
}
