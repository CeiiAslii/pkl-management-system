<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\AccountStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentApprovalControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_review_and_approve_a_student_using_server_identity_and_time(): void
    {
        $this->travelTo('2026-09-09 12:00:00');
        $admin = User::factory()->admin()->create();
        $student = StudentProfile::factory()->create()->user;
        $this->actingAs($admin)->get(route('student-approvals.index'))->assertOk()->assertSee($student->email);

        $this->post(route('student-approvals.store', $student), [
            'approved_by' => $student->id, 'approved_at' => '2000-01-01', 'role' => 'admin',
        ])->assertRedirectToRoute('student-approvals.index');

        $this->assertDatabaseHas('users', [
            'id' => $student->id, 'status' => 'active', 'role' => 'student',
            'approved_by' => $admin->id, 'approved_at' => '2026-09-09 12:00:00',
        ]);
        $this->post(route('logout'));
        $this->post(route('login'), ['identifier' => $student->email, 'password' => 'password'])->assertRedirectToRoute('account');
        $this->assertAuthenticatedAs($student);
    }

    public function test_teacher_cannot_review_or_approve_students(): void
    {
        $student = StudentProfile::factory()->create()->user;

        $this->actingAs(User::factory()->teacher()->create())->get(route('student-approvals.index'))->assertForbidden();
        $this->post(route('student-approvals.store', $student))->assertForbidden();

        $this->assertSame(AccountStatus::Pending, $student->fresh()->status);
        $this->assertNull($student->fresh()->approved_by);
    }

    public function test_admin_can_reject_a_pending_student_but_other_roles_cannot(): void
    {
        $student = StudentProfile::factory()->create()->user;

        $this->actingAs(User::factory()->teacher()->create())
            ->post(route('admin.registrations.reject', $student))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.registrations.reject', $student))
            ->assertRedirect(route('student-approvals.index'));

        $this->assertSame(AccountStatus::Suspended, $student->fresh()->status);
        $this->assertNull($student->fresh()->approved_at);
    }

    public function test_guest_cannot_approve_a_student(): void
    {
        $student = StudentProfile::factory()->create()->user;

        $this->post(route('student-approvals.store', $student))->assertRedirectToRoute('login');

        $this->assertSame(AccountStatus::Pending, $student->fresh()->status);
    }

    public function test_repeated_approval_cannot_overwrite_the_original_approver(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create()->user;
        $original = $student->approved_by;

        $this->actingAs(User::factory()->admin()->create())->post(route('student-approvals.store', $student))->assertForbidden();

        $this->assertSame($original, $student->fresh()->approved_by);
    }

    public function test_approval_rejects_non_students_and_allows_profile_less_pending_students(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create(['status' => AccountStatus::Pending]);
        $student = User::factory()->create();

        $this->actingAs($admin)->post(route('student-approvals.store', $teacher))->assertForbidden();
        $this->post(route('student-approvals.store', $student))->assertRedirectToRoute('student-approvals.index');

        $this->assertSame(AccountStatus::Pending, $teacher->fresh()->status);
        $this->assertSame(AccountStatus::Active, $student->fresh()->status);
    }

    public function test_approval_page_escapes_untrusted_student_details(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->state(['name' => '<script>alert(1)</script>']))->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('student-approvals.index'))
            ->assertSee($student->user->name)->assertDontSee($student->user->name, false);
    }
}
