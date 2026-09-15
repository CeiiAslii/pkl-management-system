<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Support\ActiveStudentScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ActiveStudentScope $activeStudents): View
    {
        $studentIds = $activeStudents->query()->select('id');
        $today = today();

        return view('teacher.dashboard', [
            'totalStudents' => (clone $studentIds)->count(),
            'pendingPermission' => LeaveRequest::query()->whereIn('student_profile_id', clone $studentIds)->where('status', 'pending')->where('type', 'izin')->count(),
            'pendingSick' => LeaveRequest::query()->whereIn('student_profile_id', clone $studentIds)->where('status', 'pending')->where('type', 'sakit')->count(),
            'reportsToday' => DailyReport::query()->whereIn('student_profile_id', clone $studentIds)->whereDate('report_date', $today)->count(),
            'attendedToday' => Attendance::query()->whereIn('student_profile_id', clone $studentIds)->whereDate('attendance_date', $today)->count(),
            'recentReports' => DailyReport::query()->whereIn('student_profile_id', clone $studentIds)->with('studentProfile.user')->latest()->limit(5)->get(),
            'pendingRequests' => LeaveRequest::query()->whereIn('student_profile_id', clone $studentIds)->where('status', 'pending')->with('studentProfile.user')->latest()->limit(5)->get(),
            'recentStudents' => $activeStudents->query()->with(['user.major'])->latest()->limit(5)->get(),
        ]);
    }
}
