<?php

namespace Database\Factories;

use App\Models\DailyReport;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
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
            'report_date' => fake()->dateTimeBetween('-30 days', 'today')->format('Y-m-d'),
            'activity_description' => fake()->paragraph(),
            'activity_photo_path' => null,
            'activity_photo_original_path' => null,
            'had_photo' => false,
            'photo_delivery_token' => null,
            'photo_telegram_sent_at' => null,
        ];
    }
}
