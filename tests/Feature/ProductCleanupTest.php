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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductCleanupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_shows_three_recent_own_notes_and_retains_attendance_actions(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $teacher = TeacherProfile::factory()->for(User::factory()->teacher()->state(['name' => 'Guru Demo']))->create();
        TeacherNote::factory()->for($profile)->for($teacher)->create(['content' => 'Catatan lama', 'created_at' => now()->subDays(4)]);
        foreach ([1, 2, 3] as $day) {
            TeacherNote::factory()->for($profile)->for($teacher)->create(['content' => 'Catatan saya '.$day, 'created_at' => now()->subDays($day)]);
        }
        TeacherNote::factory()->create(['content' => 'Catatan pribadi murid lain']);
        $response = $this->actingAs($profile->user)->get(route('student.dashboard'));
        $response->assertSee('Catatan Guru')->assertSee('Guru Demo')->assertSee('Catatan saya 1')->assertSee('Catatan saya 3')
            ->assertDontSee('Catatan lama')->assertDontSee('Catatan pribadi murid lain')->assertDontSee('Informasi PKL')
            ->assertSee('Absen Masuk')->assertSee(route('student.attendance'))->assertSee('Profil belum lengkap');
        preg_match_all('/<nav\b[^>]*>.*?<\/nav>/s', $response->getContent(), $navigation);
        $this->assertStringNotContainsString('Absensi', implode('', $navigation[0]));
        $this->get(route('student.attendance'))->assertOk();
    }

    public function test_admin_metrics_exclude_inactive_and_deleted_students(): void
    {
        $active = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $pending = StudentProfile::factory()->create();
        $deleted = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $deleted->user->delete();
        foreach ([$active, $pending, $deleted] as $profile) {
            Attendance::factory()->for($profile)->create(['attendance_date' => today()]);
            DailyReport::factory()->for($profile)->create(['report_date' => today()]);
            LeaveRequest::factory()->for($profile)->create(['status' => 'pending']);
        }
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertViewHas('activeStudents', 1)->assertViewHas('attendedToday', 1)->assertViewHas('notAttendedToday', 0)
            ->assertViewHas('reportsToday', 1)->assertViewHas('pendingLeaves', 1)
            ->assertSee('Pendaftaran Terbaru')->assertSee('Laporan Hari Ini')->assertSee('Tambah Guru');
    }

    public function test_nis_is_removed_and_obsolete_input_cannot_modify_student(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->assertFalse(Schema::hasColumn('student_profiles', 'nis'));
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.students.edit', $profile->user))
            ->assertSee('AKUN SISWA')->assertSee('DATA PKL')->assertDontSee('name="nis"', false)
            ->assertDontSee('name="pkl_latitude"', false);
        $this->patchJson(route('admin.students.update', $profile->user), [
            'name' => 'Tidak berubah', 'email' => $profile->user->email, 'major_id' => Major::factory()->create()->id,
            'status' => 'active', 'nis' => 'obsolete',
        ])->assertUnprocessable()->assertJsonValidationErrors('nis');
        $this->assertNotSame('Tidak berubah', $profile->user->fresh()->name);
    }

    public function test_report_form_and_mobile_list_use_private_photo_routes(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $report = DailyReport::factory()->for($profile)->create(['activity_photo_path' => 'private/path.png']);
        $this->actingAs($profile->user)->get(route('student.daily-reports.edit', $report))
            ->assertSee('Tanggal kegiatan')->assertSee('Ceritakan pekerjaan atau kegiatan yang dilakukan selama PKL.')
            ->assertSee('name="remove_photo"', false)->assertSee(route('student.daily-reports.photo', $report))->assertDontSee('private/path.png');
        $this->get(route('student.daily-reports.index'))->assertSee('md:hidden')->assertSee('Ubah')->assertDontSee('private/path.png');
    }
}
