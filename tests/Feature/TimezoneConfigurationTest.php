<?php

namespace Tests\Feature;

use App\Jobs\SendAttendanceTelegramNotification;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TimezoneConfigurationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_application_uses_the_makassar_timezone(): void
    {
        $this->assertSame('Asia/Makassar', config('app.timezone'));
        $this->assertSame('Asia/Makassar', date_default_timezone_get());

        $this->travelTo(CarbonImmutable::parse('2026-09-10 23:30:00', 'UTC'));

        $this->assertSame('2026-09-11 07:30:00', now()->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Makassar', now()->getTimezone()->getName());
    }

    public function test_attendance_server_timestamps_and_student_teacher_pages_use_wita(): void
    {
        Queue::fake([SendAttendanceTelegramNotification::class]);
        Storage::fake('local');
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $teacher = TeacherProfile::factory()->create();

        $this->travelTo(CarbonImmutable::parse('2026-09-10 23:30:00', 'UTC'));
        $this->actingAs($student->user)->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');

        $attendance = Attendance::query()->sole();
        $this->assertSame('2026-09-11', $attendance->attendance_date->toDateString());
        $this->assertSame('2026-09-11 07:30:00', $attendance->check_in_at->format('Y-m-d H:i:s'));

        $this->travelTo(CarbonImmutable::parse('2026-09-11 08:45:00', 'UTC'));
        $this->post(route('student.attendance.check-out'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');

        $this->get(route('student.attendance'))
            ->assertOk()
            ->assertSee('07:30')
            ->assertSee('16:45');

        $this->actingAs($teacher->user)->get(route('teacher.attendance'))
            ->assertOk()
            ->assertSee('07:30')
            ->assertSee('16:45');
    }

    public function test_report_leave_teacher_and_admin_pages_use_wita_server_dates(): void
    {
        Queue::fake();
        $this->travelTo(CarbonImmutable::parse('2026-09-11 09:20:00', 'UTC'));
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $teacher = TeacherProfile::factory()->create();
        $admin = User::factory()->admin()->create();
        $report = DailyReport::factory()->for($student)->create([
            'report_date' => today(),
            'created_at' => now(),
        ]);
        LeaveRequest::factory()->for($student)->create([
            'requested_for' => today(),
            'created_at' => now(),
        ]);

        $this->assertSame('2026-09-11 17:20:00', $report->created_at->format('Y-m-d H:i:s'));

        $this->actingAs($student->user)->get(route('student.leave-requests.index'))
            ->assertOk()
            ->assertSee('11 Sep 2026');

        $this->actingAs($teacher->user)->get(route('teacher.daily-reports.index'))
            ->assertOk()
            ->assertSee('11 Sep 2026 17:20');

        $this->actingAs($admin)->get(route('admin.teachers.show', $teacher->user))
            ->assertOk()
            ->assertSee('11 Sep 2026 17:20');
    }

    /** @return array<string, int|float|string> */
    private function attendancePayload(): array
    {
        return [
            'latitude' => 1.0,
            'longitude' => 2.0,
            'accuracy' => 9.25,
            'selfie' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        ];
    }
}
