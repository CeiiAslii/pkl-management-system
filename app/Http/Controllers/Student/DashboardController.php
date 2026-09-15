<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $studentProfile = $request->user()->studentProfile;
        $todayAttendance = $studentProfile?->attendances()->whereDate('attendance_date', today())->first();

        return view('student.dashboard', [
            'studentProfile' => $studentProfile,
            'dailyReports' => $studentProfile?->dailyReports()->latest('report_date')->limit(3)->get() ?? collect(),
            'leaveRequests' => $studentProfile?->leaveRequests()->latest('requested_for')->limit(3)->get() ?? collect(),
            'teacherNotes' => $studentProfile?->teacherNotes()->with('teacherProfile.user')->latest()->orderByDesc('id')->limit(3)->get() ?? collect(),
            'todayAttendance' => $todayAttendance,
            'todayLeaveRequest' => $studentProfile?->leaveRequests()->whereDate('requested_for', today())->latest()->first(),
            'hasReportToday' => $studentProfile?->dailyReports()->whereDate('report_date', today())->exists() ?? false,
        ]);
    }

    public function history(Request $request): View
    {
        $studentProfile = $request->user()->studentProfile;

        return view('student.history', [
            'dailyReports' => $studentProfile?->dailyReports()->latest('report_date')->limit(10)->get() ?? collect(),
            'leaveRequests' => $studentProfile?->leaveRequests()->latest('requested_for')->limit(10)->get() ?? collect(),
            'attendances' => $studentProfile?->attendances()->latest('attendance_date')->limit(10)->get() ?? collect(),
        ]);
    }
}
