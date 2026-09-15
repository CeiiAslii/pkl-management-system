<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $active = fn (Builder $query): Builder => $query->where('role', Role::Student)->where('status', AccountStatus::Active)->whereNotNull('approved_at')->whereNotNull('approved_by');
        $activeStudents = User::query()->where($active)->count();
        $attendance = Attendance::query()->whereHas('studentProfile.user', $active)->whereDate('attendance_date', $today);
        $reports = DailyReport::query()->whereHas('studentProfile.user', $active)->whereDate('report_date', $today);
        $leaves = LeaveRequest::query()->whereHas('studentProfile.user', $active)->where('status', 'pending');
        $attendedToday = $attendance->count();

        return view('admin.dashboard', [
            'activeStudents' => $activeStudents,
            'totalTeachers' => User::query()->where('role', Role::Teacher)->count(),
            'pendingStudents' => User::query()->where('role', Role::Student)->where('status', AccountStatus::Pending)->count(),
            'attendedToday' => $attendedToday,
            'notAttendedToday' => max(0, $activeStudents - $attendedToday),
            'reportsToday' => $reports->count(),
            'pendingLeaves' => $leaves->count(),
            'latestRegistrations' => User::query()->where('role', Role::Student)->with('major')->latest()->limit(5)->get(),
            'latestReports' => $reports->with('studentProfile.user.major')->latest()->limit(5)->get(),
        ]);
    }
}
