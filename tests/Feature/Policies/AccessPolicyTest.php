<?php

namespace Tests\Feature\Policies;

use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccessPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['admin', true])]
    #[TestWith(['teacher', false])]
    #[TestWith(['approvedStudent', false])]
    #[TestWith(['suspended', false])]
    public function test_only_active_admins_can_approve_students_and_manage_majors(string $state, bool $allowed): void
    {
        $user = User::factory()->{$state}()->create();
        $major = Major::factory()->create();
        $student = StudentProfile::factory()->create();
        $gate = Gate::forUser($user);

        $this->assertSame($allowed, $gate->allows('approve', $student->user));
        $this->assertSame($allowed, $gate->allows('create', Major::class));
        $this->assertSame($allowed, $gate->allows('update', $major));
        $this->assertSame($allowed, $gate->allows('delete', $major));
    }

    public function test_students_only_manage_their_own_profile_and_teachers_only_view_profiles(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $other = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $teacher = TeacherProfile::factory()->create()->user;

        $this->assertTrue(Gate::forUser($profile->user)->allows('update', $profile));
        $this->assertFalse(Gate::forUser($profile->user)->allows('view', $other));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $profile));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $profile));
    }

    public function test_non_student_with_anomalous_student_profile_cannot_create_student_records(): void
    {
        $teacher = User::factory()->teacher()->create();
        StudentProfile::factory()->for($teacher)->create();

        $this->assertFalse(Gate::forUser($teacher)->allows('create', DailyReport::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('create', LeaveRequest::class));
    }
}
