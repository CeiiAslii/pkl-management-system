<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminStudentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_list_dropdown_filters_auto_submit_without_a_filter_button(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.students.index', ['search' => 'Murid', 'status' => 'active']))
            ->assertOk()
            ->assertSee('name="major" onchange="this.form.submit()"', false)
            ->assertDontSee('name="class"', false)
            ->assertSee('name="status" onchange="this.form.submit()"', false)
            ->assertSee('name="search" value="Murid"', false)
            ->assertDontSee('>Filter</button>', false);
    }

    public function test_admin_can_view_and_edit_safe_student_data(): void
    {
        $studentProfile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $major = Major::factory()->create();
        $admin = User::factory()->admin()->create();
        $data = [
            'name' => 'Murid Diperbarui', 'email' => 'murid.diperbarui@example.test', 'major_id' => $major->id,
            'phone' => '0800000000',
            'pkl_place_name' => 'CV Belajar Maju',
            'pkl_contact_name' => 'PIC Demo', 'pkl_phone' => '08000000', 'status' => 'active',
        ];

        $this->actingAs($admin)->get(route('admin.students.show', $studentProfile->user))->assertOk()->assertSee($studentProfile->user->email);
        $this->patch(route('admin.students.update', $studentProfile->user), $data)->assertRedirectToRoute('admin.students.show', $studentProfile->user);

        $student = $studentProfile->user->fresh();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => $data['name'], 'email' => $data['email'], 'major_id' => $major->id, 'role' => 'student']);
        $this->assertDatabaseHas('student_profiles', ['id' => $studentProfile->id, 'phone' => '0800000000', 'pkl_place_name' => 'CV Belajar Maju']);
    }

    public function test_admin_cannot_change_role_or_submit_removed_relationships(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $data = $this->studentData($profile, ['school_class_id' => 999, 'pkl_place_id' => 999, 'role' => 'admin']);

        $this->actingAs(User::factory()->admin()->create())->patchJson(route('admin.students.update', $profile->user), $data)
            ->assertUnprocessable()->assertJsonValidationErrors(['school_class_id', 'pkl_place_id', 'role']);

        $this->assertSame('student', $profile->user->fresh()->role->value);
    }

    public function test_only_admin_can_edit_or_delete_students(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        foreach ([User::factory()->teacher()->create(), User::factory()->approvedStudent()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.students.edit', $profile->user))->assertForbidden();
            $this->patch(route('admin.students.update', $profile->user), $this->studentData($profile))->assertForbidden();
            $this->delete(route('admin.students.destroy', $profile->user))->assertForbidden();
        }

        $this->assertDatabaseHas('users', ['id' => $profile->user_id]);
    }

    public function test_admin_soft_deletes_a_student_without_orphaning_history_and_deleted_user_cannot_login(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $dailyReport = DailyReport::factory()->for($profile)->create();
        $leaveRequest = LeaveRequest::factory()->for($profile)->create();
        $student = $profile->user;

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.students.destroy', $student))
            ->assertRedirectToRoute('admin.students.index');

        $this->assertSoftDeleted('users', ['id' => $student->id]);
        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('daily_reports', ['id' => $dailyReport->id, 'student_profile_id' => $profile->id]);
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'student_profile_id' => $profile->id]);
        $this->get(route('admin.students.index'))->assertDontSee($student->email);
        $this->post(route('logout'));
        $this->postJson(route('login'), ['identifier' => $student->email, 'password' => 'password'])->assertUnprocessable();
    }

    /** @param array<string, mixed> $overrides */
    private function studentData(StudentProfile $profile, array $overrides = []): array
    {
        return [...[
            'name' => $profile->user->name, 'email' => $profile->user->email, 'major_id' => $profile->user->major_id,
            'phone' => $profile->phone,
            'status' => 'active',
        ], ...$overrides];
    }
}
