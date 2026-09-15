<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_student_can_access_dashboard_without_a_permanent_pkl_card(): void
    {
        $profile = $this->approvedStudentProfile();
        $profile->update(['pkl_place_name' => 'PT PKL Murid', 'pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5]);

        $this->actingAs($profile->user)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee($profile->user->name)
            ->assertDontSee('PT PKL Murid')
            ->assertSee('Belum absen')
            ->assertSee('Navigasi utama murid');
    }

    public function test_student_dashboard_actions_follow_today_attendance_state(): void
    {
        $profile = $this->approvedStudentProfile();
        $attendance = Attendance::factory()->for($profile)->create();

        $this->actingAs($profile->user)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Sudah masuk')
            ->assertSee('Masuk selesai')
            ->assertSee('Absen Pulang');

        $attendance->update(['check_out_at' => now()]);
        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Sudah pulang')
            ->assertSee('Pulang selesai');
    }

    public function test_pending_students_are_denied_from_student_dashboard_routes(): void
    {
        $profile = StudentProfile::factory()->create();

        $this->actingAs($profile->user)->get(route('student.dashboard'))->assertForbidden();
        $this->assertGuest();
    }

    public function test_admins_and_teachers_cannot_access_student_dashboard_routes(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->teacher()->create()] as $user) {
            $this->actingAs($user)->get(route('student.dashboard'))->assertForbidden();
            $this->get(route('student.daily-reports.index'))->assertForbidden();
        }
    }

    public function test_student_sees_only_their_own_daily_reports(): void
    {
        $profile = $this->approvedStudentProfile();
        DailyReport::factory()->for($profile)->create(['activity_description' => 'Laporan milik saya']);
        DailyReport::factory()->create(['activity_description' => 'Laporan murid lain']);

        $this->actingAs($profile->user)->get(route('student.daily-reports.index'))
            ->assertOk()
            ->assertSee('Laporan milik saya')
            ->assertDontSee('Laporan murid lain');
    }

    public function test_previous_daily_reports_remain_in_history_while_dashboard_tracks_only_today(): void
    {
        $profile = $this->approvedStudentProfile();
        $yesterday = DailyReport::factory()->for($profile)->create([
            'report_date' => today()->subDay(),
            'activity_description' => 'Laporan kemarin tetap tersimpan.',
        ]);

        $this->actingAs($profile->user)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Isi Laporan Harian');
        $this->get(route('student.history'))
            ->assertOk()
            ->assertSee('Laporan kemarin tetap tersimpan.');

        DailyReport::factory()->for($profile)->create([
            'report_date' => today(),
            'activity_description' => 'Laporan hari ini.',
        ]);

        $this->get(route('student.dashboard'))->assertSee('Laporan Hari Ini Selesai');
        $this->assertDatabaseHas('daily_reports', ['id' => $yesterday->id]);
        $this->assertDatabaseCount('daily_reports', 2);
    }

    public function test_scheduled_photo_cleanup_keeps_daily_report_history(): void
    {
        $descriptions = collect($this->app->make(Schedule::class)->events())
            ->map(fn ($event) => strtolower(($event->description ?? '').' '.($event->command ?? '')));

        $this->assertTrue($descriptions->contains(
            fn (string $description): bool => str_contains($description, 'daily_report')
                || str_contains($description, 'daily-report')
                || str_contains($description, 'laporan harian'),
        ));
    }

    public function test_student_cannot_edit_or_delete_another_students_daily_report(): void
    {
        $dailyReport = DailyReport::factory()->create(['activity_description' => 'Data pribadi murid lain']);
        $student = $this->approvedStudentProfile()->user;

        $this->actingAs($student)->get(route('student.daily-reports.edit', $dailyReport))
            ->assertNotFound()
            ->assertDontSee('Data pribadi murid lain');
        $this->delete(route('student.daily-reports.destroy', $dailyReport))->assertNotFound();

        $this->assertDatabaseHas('daily_reports', ['id' => $dailyReport->id]);
    }

    public function test_daily_report_uses_authenticated_students_profile_and_prevents_duplicates(): void
    {
        $profile = $this->approvedStudentProfile();
        $data = [
            'report_date' => '2026-09-10',
            'activity_description' => 'Membantu perawatan perangkat jaringan.',
        ];

        $this->actingAs($profile->user)->post(route('student.daily-reports.store'), [...$data, 'student_profile_id' => 999])
            ->assertSessionHasErrors('student_profile_id');
        $this->post(route('student.daily-reports.store'), $data)->assertRedirectToRoute('student.daily-reports.index');
        $dailyReport = DailyReport::query()->firstOrFail();
        $this->assertSame('2026-09-10', $dailyReport->report_date->toDateString());
        $this->assertSame($data['activity_description'], $dailyReport->activity_description);
        $this->assertSame($profile->id, $dailyReport->student_profile_id);

        $this->postJson(route('student.daily-reports.store'), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('report_date');
    }

    public function test_daily_report_upload_requires_an_image_and_private_photo_is_limited_to_its_owner(): void
    {
        Storage::fake('local');
        $profile = $this->approvedStudentProfile();
        $data = [
            'report_date' => '2026-09-10',
            'activity_description' => 'Menyusun inventaris perangkat.',
            'activity_photo' => UploadedFile::fake()->createWithContent('kegiatan.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true)),
        ];

        $this->actingAs($profile->user)->post(route('student.daily-reports.store'), $data)
            ->assertRedirectToRoute('student.daily-reports.index');
        $dailyReport = DailyReport::query()->firstOrFail();
        Storage::disk('local')->assertExists($dailyReport->activity_photo_path);

        $this->actingAs($this->approvedStudentProfile()->user)
            ->get(route('student.daily-reports.photo', $dailyReport))
            ->assertNotFound();
        $this->actingAs($profile->user)->get(route('student.daily-reports.photo', $dailyReport))->assertOk();

        $this->withHeaders(['Accept' => 'application/json'])->actingAs($profile->user)
            ->post(route('student.daily-reports.store'), [
                'report_date' => '2026-09-11',
                'activity_description' => 'Data yang tidak valid.',
                'activity_photo' => UploadedFile::fake()->create('catatan.txt', 10, 'text/plain'),
            ])->assertUnprocessable()->assertJsonValidationErrors('activity_photo');
    }

    public function test_daily_report_history_distinguishes_available_archived_and_missing_photos(): void
    {
        Storage::fake('local');
        $profile = $this->approvedStudentProfile();
        $available = DailyReport::factory()->for($profile)->create([
            'report_date' => today(),
            'activity_photo_path' => 'daily-report-photos/available.jpg',
            'had_photo' => true,
            'activity_description' => 'Foto yang masih tersedia.',
        ]);
        Storage::disk('local')->put($available->activity_photo_path, 'compressed');
        $archived = DailyReport::factory()->for($profile)->create([
            'report_date' => today()->subDay(),
            'activity_photo_path' => null,
            'had_photo' => true,
            'activity_description' => 'Foto yang sudah diarsipkan.',
        ]);
        DailyReport::factory()->for($profile)->create([
            'report_date' => today()->subDays(2),
            'activity_photo_path' => null,
            'had_photo' => false,
            'activity_description' => 'Laporan tanpa foto.',
        ]);

        $response = $this->actingAs($profile->user)->get(route('student.daily-reports.index'));

        $response->assertOk()
            ->assertSee('Lihat foto')
            ->assertSee('Foto telah diarsipkan')
            ->assertSee('Tidak ada foto')
            ->assertSee(route('student.daily-reports.photo', $available), false)
            ->assertDontSee(route('student.daily-reports.photo', $archived), false);
    }

    public function test_student_sees_only_their_own_leave_requests_and_cannot_approve_one(): void
    {
        $profile = $this->approvedStudentProfile();
        LeaveRequest::factory()->for($profile)->create(['reason' => 'Izin milik saya']);
        $otherLeaveRequest = LeaveRequest::factory()->create(['reason' => 'Izin murid lain']);

        $this->actingAs($profile->user)->get(route('student.leave-requests.index'))
            ->assertOk()
            ->assertSee('Izin milik saya')
            ->assertDontSee('Izin murid lain');
        $this->assertFalse(Gate::forUser($profile->user)->allows('approve', $otherLeaveRequest));
    }

    public function test_leave_request_uses_authenticated_profile_and_validates_private_uploads(): void
    {
        Storage::fake('local');
        $profile = $this->approvedStudentProfile();
        $data = [
            'requested_for' => '2026-09-10',
            'type' => 'sakit',
            'reason' => 'Perlu berobat.',
            'supporting_file' => UploadedFile::fake()->create('surat.pdf', 20, 'application/pdf'),
        ];

        $this->actingAs($profile->user)->post(route('student.leave-requests.store'), [...$data, 'status' => 'approved'])
            ->assertSessionHasErrors('status');
        $this->post(route('student.leave-requests.store'), $data)->assertRedirectToRoute('student.leave-requests.index');
        $leaveRequest = LeaveRequest::query()->firstOrFail();
        $this->assertSame('pending', $leaveRequest->status);
        $this->assertSame($profile->id, $leaveRequest->student_profile_id);
        Storage::disk('local')->assertExists($leaveRequest->supporting_file_path);

        $this->actingAs($this->approvedStudentProfile()->user)
            ->get(route('student.leave-requests.file', $leaveRequest))
            ->assertNotFound();
        $this->actingAs($profile->user)->get(route('student.leave-requests.file', $leaveRequest))->assertOk();

        $this->withHeaders(['Accept' => 'application/json'])->actingAs($profile->user)
            ->post(route('student.leave-requests.store'), [
                'requested_for' => '2026-09-11',
                'type' => 'izin',
                'reason' => 'Tidak valid.',
                'supporting_file' => UploadedFile::fake()->create('catatan.exe', 10, 'application/octet-stream'),
            ])->assertUnprocessable()->assertJsonValidationErrors('supporting_file');
    }

    public function test_student_can_edit_only_safe_profile_fields(): void
    {
        $profile = $this->approvedStudentProfile();
        $this->actingAs($profile->user)->patch(route('student.profile.update'), [
            'name' => 'Nama Murid Baru',
            'email' => 'murid.baru@example.test',
            'phone' => '+62 800-0000',
        ])->assertRedirectToRoute('student.profile.show');

        $this->assertDatabaseHas('users', ['id' => $profile->user_id, 'name' => 'Nama Murid Baru', 'email' => 'murid.baru@example.test', 'role' => 'student', 'status' => 'active']);
        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'phone' => '+62 800-0000']);

        $this->patchJson(route('student.profile.update'), [
            'name' => 'Nama Murid Baru',
            'email' => 'murid.baru@example.test',
            'role' => 'admin',
            'status' => 'suspended',
            'school_class_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['role', 'status', 'school_class_id']);

        $this->assertSame(AccountStatus::Active, $profile->user->fresh()->status);
    }

    public function test_student_can_fill_their_own_direct_pkl_information(): void
    {
        $profile = $this->approvedStudentProfile();
        $this->actingAs($profile->user)->patch(route('student.profile.update'), [
            'name' => $profile->user->name,
            'email' => $profile->user->email,
            'phone' => $profile->phone,
            'pkl_place_name' => 'Tempat PKL Saya',
            'pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5,
            'pkl_contact_name' => 'PIC Demo',
            'pkl_phone' => '08000000',
        ])->assertRedirectToRoute('student.profile.show');

        $profile->refresh();
        $this->assertSame('Tempat PKL Saya', $profile->pkl_place_name);
        $this->assertSame('1.0000000', $profile->pkl_latitude);
        $this->assertSame('2.0000000', $profile->pkl_longitude);
        $this->assertSame('PIC Demo', $profile->pkl_contact_name);
        $this->assertSame('08000000', $profile->pkl_contact_phone);
        $this->actingAs($profile->user)->get(route('student.profile.show'))->assertSee('Tempat PKL Saya');
    }

    public function test_profile_update_returns_validation_error_when_gps_is_incomplete(): void
    {
        $profile = $this->approvedStudentProfile();
        $originalPlaceName = $profile->pkl_place_name;

        $this->actingAs($profile->user)->patchJson(route('student.profile.update'), [
            'name' => 'Nama Tidak Jadi Berubah',
            'email' => 'tidak-jadi@example.test',
            'pkl_place_name' => 'Perusahaan Demo A',
            'pkl_latitude' => 1.0,
            'pkl_longitude' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('pkl_longitude');

        $this->assertNotSame('Nama Tidak Jadi Berubah', $profile->user->fresh()->name);
        $this->assertSame($originalPlaceName, $profile->fresh()->pkl_place_name);
    }

    public function test_student_can_crop_and_replace_their_private_profile_photo_safely(): void
    {
        Storage::fake('local');
        $profile = $this->approvedStudentProfile();
        $profile->update(['profile_photo_path' => 'student-profile-photos/old-photo.jpg']);
        Storage::disk('local')->put('student-profile-photos/old-photo.jpg', 'old');

        $this->actingAs($profile->user)->patch(route('student.profile.update'), [
            'name' => $profile->user->name,
            'email' => $profile->user->email,
            'profile_photo' => $this->squarePng('cropped-profile.png'),
        ])->assertRedirectToRoute('student.profile.show');

        $newPath = $profile->fresh()->profile_photo_path;
        $this->assertNotSame('student-profile-photos/old-photo.jpg', $newPath);
        $this->assertMatchesRegularExpression('/^student-profile-photos\/[0-9a-f-]+\.png$/', $newPath);
        Storage::disk('local')->assertExists($newPath);
        Storage::disk('local')->assertMissing('student-profile-photos/old-photo.jpg');
        $this->get(route('student.profile.show'))
            ->assertOk()
            ->assertDontSee($newPath)
            ->assertSee(route('student.profile.photo'));
        $this->get(route('student.profile.photo'))->assertOk();
    }

    public function test_profile_photo_rejects_non_images_and_oversized_files(): void
    {
        Storage::fake('local');
        $profile = $this->approvedStudentProfile();
        $baseData = ['name' => $profile->user->name, 'email' => $profile->user->email];

        $this->actingAs($profile->user)->patchJson(route('student.profile.update'), [
            ...$baseData,
            'profile_photo' => UploadedFile::fake()->create('payload.png', 10, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('profile_photo');

        $this->patchJson(route('student.profile.update'), [
            ...$baseData,
            'profile_photo' => UploadedFile::fake()->create('terlalu-besar.png', 2049, 'image/png'),
        ])->assertUnprocessable()->assertJsonValidationErrors('profile_photo');
        $this->assertNull($profile->fresh()->profile_photo_path);
    }

    public function test_student_cannot_replace_another_students_profile_photo(): void
    {
        Storage::fake('local');
        $victim = $this->approvedStudentProfile();
        $victim->update(['profile_photo_path' => 'student-profile-photos/victim.png']);
        Storage::disk('local')->put($victim->profile_photo_path, 'victim');
        $attacker = $this->approvedStudentProfile();

        $this->actingAs($attacker->user)->patchJson(route('student.profile.update'), [
            'name' => $attacker->user->name,
            'email' => $attacker->user->email,
            'student_profile_id' => $victim->id,
            'profile_photo' => $this->squarePng('attacker.png'),
        ])->assertUnprocessable()->assertJsonValidationErrors('student_profile_id');

        $this->assertSame('student-profile-photos/victim.png', $victim->fresh()->profile_photo_path);
        Storage::disk('local')->assertExists('student-profile-photos/victim.png');
    }

    private function squarePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );
    }

    private function approvedStudentProfile(): StudentProfile
    {
        return StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
    }
}
