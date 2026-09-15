<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_admin_can_access_dashboard_and_see_operational_statistics(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        User::factory()->teacher()->create();
        User::factory()->create();
        Attendance::factory()->for($profile)->create(['attendance_date' => today()]);
        DailyReport::factory()->for($profile)->create(['report_date' => today()]);
        DailyReport::factory()->for($profile)->create([
            'report_date' => today()->subDay(),
            'activity_description' => 'Laporan kemarin tidak boleh tampil di dashboard.',
        ]);
        LeaveRequest::factory()->for($profile)->create(['status' => 'pending']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Murid Aktif')
            ->assertSee('Sudah Absen Hari Ini')
            ->assertSee('Laporan Hari Ini')
            ->assertSee('Izin/Sakit Menunggu')
            ->assertDontSee('Tahun Ajaran')
            ->assertDontSee('Laporan kemarin tidak boleh tampil di dashboard.');
    }

    public function test_students_and_teachers_cannot_access_admin_routes(): void
    {
        foreach ([User::factory()->approvedStudent()->create(), User::factory()->teacher()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        }
    }

    public function test_root_route_redirects_guests_to_login(): void
    {
        $this->get(route('home'))->assertRedirectToRoute('login');
    }

    public function test_empty_today_report_state_does_not_show_historical_reports(): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        DailyReport::factory()->for($profile)->create([
            'report_date' => today()->subDay(),
            'activity_description' => 'Laporan historis tetap tersimpan.',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertSee('Laporan Hari Ini')
            ->assertSee('Belum ada laporan hari ini.')
            ->assertDontSee('Laporan historis tetap tersimpan.');

        $this->assertDatabaseCount('daily_reports', 1);
    }
}
