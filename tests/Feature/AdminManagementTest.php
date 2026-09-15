<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_manage_majors_and_referenced_deletion_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.majors.store'), ['code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan'])->assertRedirect(route('admin.majors.index'));
        $major = Major::query()->firstOrFail();

        $this->put(route('admin.majors.update', $major), ['code' => '', 'name' => ''])->assertSessionHasErrors(['code', 'name']);
        User::factory()->approvedStudent()->for($major)->create();
        $this->delete(route('admin.majors.destroy', $major))->assertSessionHasErrors('major');
    }

    public function test_non_admins_are_forbidden_from_data_management_and_other_users_records(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = StudentProfile::factory()->create()->user;
        $otherStudent = StudentProfile::factory()->create()->user;
        $teacherProfile = TeacherProfile::factory()->create();

        $this->actingAs($teacher)->post(route('admin.majors.store'), ['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak'])->assertForbidden();
        $this->get(route('admin.students.show', $student))->assertForbidden();
        $this->actingAs($student)->get(route('admin.students.show', $otherStudent))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.teachers.show', $teacherProfile->user))->assertOk();
    }

    public function test_removed_admin_master_data_routes_return_not_found(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['/admin/kelas', '/admin/tempat-pkl', '/admin/tahun-ajaran'] as $uri) {
            $this->get($uri)->assertNotFound();
        }
    }
}
