<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_account_redirects_an_active_student_to_their_dashboard(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($profile->user)->get(route('account'))
            ->assertRedirectToRoute('student.dashboard');
    }

    public function test_account_redirects_an_active_teacher_to_their_dashboard(): void
    {
        $profile = TeacherProfile::factory()->create();

        $this->actingAs($profile->user)->get(route('account'))
            ->assertRedirectToRoute('teacher.dashboard');
    }
}
