<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TeacherProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_teacher_can_read_own_profile(): void
    {
        $profile = TeacherProfile::factory()->create();

        $this->actingAs($profile->user)->get(route('teacher-profiles.show', $profile))
            ->assertOk()->assertSee($profile->employee_number);
    }

    public function test_other_teachers_cannot_read_private_teacher_details(): void
    {
        $profile = TeacherProfile::factory()->create();

        $this->actingAs(User::factory()->teacher()->create())->get(route('teacher-profiles.show', $profile))
            ->assertNotFound()->assertDontSee($profile->employee_number);
    }

    public function test_guest_cannot_read_teacher_details(): void
    {
        $this->get(route('teacher-profiles.show', TeacherProfile::factory()->create()))->assertRedirectToRoute('login');
    }
}
