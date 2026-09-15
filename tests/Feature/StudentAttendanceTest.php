<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentAttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_attendance_page_explains_https_requirement_for_gps_and_camera(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($student->user)->get(route('student.attendance'))
            ->assertOk()
            ->assertSee('Absensi GPS dan kamera memerlukan HTTPS. Gunakan alamat HTTPS atau localhost untuk melakukan absensi.');
    }

    public function test_active_student_checks_in_with_server_time_gps_and_private_selfie_without_pkl_master(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2026-09-10 08:15:30');
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($student->user)->post(route('student.attendance.check-in'), array_merge($this->attendancePayload(), [
            'student_profile_id' => 999,
            'attendance_date' => '2020-01-01',
            'check_in_at' => '2020-01-01 00:00:00',
            'status' => 'approved',
        ]))->assertSessionHasErrors(['student_profile_id', 'attendance_date', 'check_in_at', 'status']);

        $this->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');

        $attendance = Attendance::query()->sole();
        $this->assertSame($student->id, $attendance->student_profile_id);
        $this->assertSame('2026-09-10', $attendance->attendance_date->toDateString());
        $this->assertSame('2026-09-10 08:15:30', $attendance->check_in_at->format('Y-m-d H:i:s'));
        $this->assertStringStartsWith("attendance-selfies/{$student->id}/", $attendance->check_in_selfie_path);
        Storage::disk('local')->assertExists($attendance->check_in_selfie_path);
    }

    public function test_check_in_requires_valid_gps_and_selfie(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($student->user)->post(route('student.attendance.check-in'), [])
            ->assertSessionHasErrors(['latitude', 'longitude', 'accuracy', 'selfie']);
        $this->post(route('student.attendance.check-in'), [
            'latitude' => 91,
            'longitude' => 181,
            'accuracy' => -1,
            'selfie' => 'data:text/plain;base64,YnVrYW4gZ2FtYmFy',
        ])->assertSessionHasErrors(['latitude', 'longitude', 'accuracy', 'selfie']);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_pending_and_suspended_students_cannot_submit_attendance(): void
    {
        $pending = StudentProfile::factory()->for(User::factory())->create();
        $this->actingAs($pending->user)->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertForbidden();
        $this->assertGuest();

        $suspended = StudentProfile::factory()->for(User::factory()->approvedStudent()->suspended())->create();
        $this->actingAs($suspended->user)->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertForbidden();
        $this->assertGuest();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_duplicate_check_in_is_rejected_and_database_unique_constraint_protects_the_day(): void
    {
        Storage::fake('local');
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($student->user)->post(route('student.attendance.check-in'), $this->attendancePayload())->assertRedirect();
        $this->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertSessionHasErrors(['attendance']);
        $this->assertDatabaseCount('attendances', 1);

        $this->expectException(QueryException::class);
        Attendance::factory()->for($student)->create(['attendance_date' => today()]);
    }

    public function test_student_can_check_in_again_next_day_and_old_attendance_remains_stored(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-09-13 08:00:00');
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($student->user)
            ->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');

        $this->travelTo('2026-09-14 08:00:00');
        $this->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');

        $this->assertSame(
            ['2026-09-13', '2026-09-14'],
            Attendance::query()->orderBy('attendance_date')->get()->map(fn (Attendance $attendance): string => $attendance->attendance_date->toDateString())->all(),
        );
    }

    public function test_checkout_updates_only_todays_attendance(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-09-14 16:00:00');
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $yesterday = Attendance::factory()->for($student)->create(['attendance_date' => '2026-09-13']);
        $today = Attendance::factory()->for($student)->create(['attendance_date' => '2026-09-14']);

        $this->actingAs($student->user)
            ->post(route('student.attendance.check-out'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');

        $this->assertNull($yesterday->fresh()->check_out_at);
        $this->assertNotNull($today->fresh()->check_out_at);
    }

    public function test_checkout_requires_check_in_uses_server_time_and_rejects_duplicate(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2026-09-10 07:50:00');
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->actingAs($student->user)->post(route('student.attendance.check-out'), $this->attendancePayload())
            ->assertSessionHasErrors(['attendance']);

        $this->post(route('student.attendance.check-in'), $this->attendancePayload())->assertRedirect();
        Carbon::setTestNow('2026-09-10 16:07:12');
        $this->post(route('student.attendance.check-out'), $this->attendancePayload([
            'latitude' => 1.001,
            'longitude' => 2.001,
            'accuracy' => 8.5,
        ]))->assertRedirectToRoute('student.attendance');

        $attendance = Attendance::query()->sole();
        $this->assertSame('2026-09-10 16:07:12', $attendance->check_out_at->format('Y-m-d H:i:s'));
        $this->assertSame('1.0010000', $attendance->check_out_latitude);
        Storage::disk('local')->assertExists($attendance->check_out_selfie_path);

        $this->post(route('student.attendance.check-out'), $this->attendancePayload())
            ->assertSessionHasErrors(['attendance']);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_attendance_selfies_are_private_and_authorized_without_idor(): void
    {
        Storage::fake('local');
        $owner = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $attendance = Attendance::factory()->checkedOut()->for($owner)->create([
            'check_in_selfie_path' => 'attendance-selfies/in.jpg',
            'check_out_selfie_path' => 'attendance-selfies/out.jpg',
        ]);
        Storage::disk('local')->put($attendance->check_in_selfie_path, 'image');
        Storage::disk('local')->put($attendance->check_out_selfie_path, 'image');

        $this->actingAs($owner->user)->get(route('private.attendances.selfie', [$attendance, 'check-in']))->assertOk();
        $other = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->actingAs($other->user)->get(route('private.attendances.selfie', [$attendance, 'check-in']))->assertNotFound();
        $teacher = TeacherProfile::factory()->create();
        $this->actingAs($teacher->user)->get(route('private.attendances.selfie', [$attendance, 'check-in']))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('private.attendances.selfie', [$attendance, 'check-out']))->assertOk();
    }

    public function test_teacher_attendance_page_defaults_to_today_and_filters_active_students(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $matching = StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['name' => 'Murid Hadir Cocok']))->create();
        Attendance::factory()->for($matching)->create(['attendance_date' => today(), 'check_in_at' => now()->setTime(7, 45)]);
        $other = StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['name' => 'Murid Hadir Lain']))->create();
        Attendance::factory()->for($other)->create(['attendance_date' => today()->subDay(), 'check_in_at' => now()->subDay()]);

        $this->actingAs($teacher->user)->get(route('teacher.attendance', ['student' => 'Hadir Cocok']))
            ->assertOk()
            ->assertSee('Murid Hadir Cocok')
            ->assertSee('07:45')
            ->assertDontSee('Murid Hadir Lain')
            ->assertDontSee('name="date"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function attendancePayload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => 1.0000000,
            'longitude' => 2.0000000,
            'accuracy' => 9.25,
            'selfie' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        ], $overrides);
    }
}
