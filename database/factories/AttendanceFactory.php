<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
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
            'attendance_date' => today(),
            'check_in_at' => now()->setTime(8, 0),
            'check_out_at' => null,
            'check_in_latitude' => 1.0000000,
            'check_in_longitude' => 2.0000000,
            'check_in_accuracy' => 12.50,
            'check_out_latitude' => null,
            'check_out_longitude' => null,
            'check_out_accuracy' => null,
            'check_in_selfie_path' => 'attendance-selfies/check-in.jpg',
            'check_out_selfie_path' => null,
            'status' => 'hadir',
            'late_status' => null,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn () => [
            'check_out_at' => now()->setTime(16, 0),
            'check_out_latitude' => 1.0001000,
            'check_out_longitude' => 2.0001000,
            'check_out_accuracy' => 10.25,
            'check_out_selfie_path' => 'attendance-selfies/check-out.jpg',
        ]);
    }
}
