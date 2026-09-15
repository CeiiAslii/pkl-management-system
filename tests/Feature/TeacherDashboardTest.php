<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\TeacherNote;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_counts_all_active_students_and_real_activity(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $active = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        StudentProfile::factory()->create();
        $deleted = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $deleted->user->delete();
        DailyReport::factory()->for($active)->create(['report_date' => today()]);
        LeaveRequest::factory()->for($active)->create(['type' => 'izin', 'status' => 'pending']);
        Attendance::factory()->for($active)->create(['attendance_date' => today()]);

        $this->actingAs($teacher->user)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Total murid')
            ->assertSee('Laporan hari ini')
            ->assertSee($active->user->name)
            ->assertDontSee($deleted->user->name);
    }

    public function test_teacher_pages_filter_by_student_and_major_without_class_filter(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $major = Major::factory()->create(['code' => 'TKJ']);
        $matching = StudentProfile::factory()->for(User::factory()->approvedStudent()->for($major)->state(['name' => 'Murid Cocok']))->create(['pkl_place_name' => 'PT Langsung']);
        StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['name' => 'Murid Lain']))->create();
        DailyReport::factory()->for($matching)->create([
            'report_date' => today(),
            'activity_description' => 'Laporan cocok',
        ]);

        foreach (['teacher.attendance', 'teacher.daily-reports.index', 'teacher.leave-requests.index', 'teacher.recap', 'teacher.notes.index'] as $route) {
            $this->actingAs($teacher->user)->get(route($route, ['student' => 'Murid Cocok', 'search' => 'Murid Cocok', 'major' => $major->id]))
                ->assertOk()
                ->assertSee('Murid Cocok')
                ->assertDontSee('name="class"', false);
        }

        $this->get(route('teacher.daily-reports.index'))->assertSee('PT Langsung');
    }

    public function test_teacher_daily_report_page_shows_only_today_while_old_report_remains_stored(): void
    {
        $this->travelTo('2026-09-13 08:00:00');
        $teacher = TeacherProfile::factory()->create();
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $yesterday = DailyReport::factory()->for($student)->create([
            'report_date' => '2026-09-12',
            'activity_description' => 'Laporan kemarin tersimpan',
        ]);
        DailyReport::factory()->for($student)->create([
            'report_date' => '2026-09-13',
            'activity_description' => 'Laporan hari ini tampil',
        ]);

        $this->actingAs($teacher->user)->get(route('teacher.daily-reports.index'))
            ->assertOk()
            ->assertSee('Laporan hari ini tampil')
            ->assertDontSee('Laporan kemarin tersimpan');
        $this->get(route('teacher.recap', ['student_profile_id' => $student->id]))
            ->assertOk()
            ->assertSee('Laporan kemarin tersimpan');

        $this->assertModelExists($yesterday);
        $this->assertDatabaseCount('daily_reports', 2);
    }

    public function test_attendance_page_shows_coordinates_and_private_selfie_actions(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        Attendance::factory()->checkedOut()->for($student)->create();

        $this->actingAs($teacher->user)->get(route('teacher.attendance'))
            ->assertOk()
            ->assertSee($student->user->name)
            ->assertSee('Lihat lokasi')
            ->assertSee('Selfie masuk')
            ->assertDontSee('Lokasi PKL belum dikonfigurasi');
    }

    public function test_teacher_reviews_leave_request_but_student_cannot(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $leave = LeaveRequest::factory()->for(StudentProfile::factory()->for(User::factory()->approvedStudent()))->create(['status' => 'pending']);

        $this->actingAs($teacher->user)->patch(route('teacher.leave-requests.review', $leave), ['status' => 'approved', 'teacher_note' => 'Disetujui'])
            ->assertRedirect();
        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id, 'status' => 'approved', 'reviewed_by' => $teacher->id]);

        $other = LeaveRequest::factory()->for(StudentProfile::factory()->for(User::factory()->approvedStudent()))->create();
        $this->actingAs($other->studentProfile->user)->patch(route('teacher.leave-requests.review', $other), ['status' => 'approved'])->assertForbidden();
    }

    public function test_recap_uses_direct_student_pkl_information(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create([
            'pkl_place_name' => 'Bengkel Mandiri',
            'pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5,
        ]);

        $this->actingAs($teacher->user)->get(route('teacher.recap', ['student_profile_id' => $student->id]))
            ->assertOk()
            ->assertSee('Bengkel Mandiri')
            ->assertSee('https://www.google.com/maps/search/?api=1&query=1.0000000%2C2.0000000')
            ->assertDontSee('Tahun ajaran');
    }

    public function test_teacher_can_manage_only_their_own_notes_for_active_students(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $otherTeacher = TeacherProfile::factory()->create();
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($teacher->user)->post(route('teacher.notes.store'), [
            'student_profile_id' => $student->id,
            'content' => 'Catatan perkembangan murid.',
            'teacher_profile_id' => $otherTeacher->id,
        ])->assertSessionHasErrors('teacher_profile_id');
        $this->post(route('teacher.notes.store'), ['student_profile_id' => $student->id, 'content' => 'Catatan perkembangan murid.'])->assertRedirect();
        $note = TeacherNote::query()->sole();
        $this->assertSame($teacher->id, $note->teacher_profile_id);

        $otherNote = TeacherNote::factory()->for($otherTeacher)->for($student)->create();
        $this->patch(route('teacher.notes.update', $otherNote), ['content' => 'Diubah'])->assertNotFound();
        $this->delete(route('teacher.notes.destroy', $otherNote))->assertNotFound();
    }

    public function test_student_and_suspended_teacher_cannot_access_teacher_panel(): void
    {
        $this->actingAs(User::factory()->approvedStudent()->create())->get(route('teacher.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->teacher()->suspended()->create())->get(route('teacher.dashboard'))->assertForbidden();
    }
}
