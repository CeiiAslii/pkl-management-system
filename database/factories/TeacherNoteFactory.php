<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\TeacherNote;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherNote>
 */
class TeacherNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_profile_id' => TeacherProfile::factory(),
            'student_profile_id' => StudentProfile::factory(),
            'content' => fake()->paragraph(),
        ];
    }
}
