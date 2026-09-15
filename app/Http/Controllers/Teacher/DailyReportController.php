<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\Major;
use App\Support\ActiveStudentScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DailyReportController extends Controller
{
    public function index(Request $request, ActiveStudentScope $activeStudents): View
    {
        $filters = $request->validate([
            'major' => ['nullable', 'integer', 'exists:majors,id'],
            'student' => ['nullable', 'string', 'max:100'],
        ]);
        $studentIds = $activeStudents->query()->select('id');

        $reports = DailyReport::query()
            ->whereIn('student_profile_id', $studentIds)
            ->whereDate('report_date', today())
            ->with(['studentProfile.user.major'])
            ->when($filters['major'] ?? null, fn ($query, $major) => $query->whereHas('studentProfile.user', fn ($query) => $query->where('major_id', $major)))
            ->when($filters['student'] ?? null, fn ($query, $student) => $query->whereHas('studentProfile.user', fn ($query) => $query->where('name', 'like', "%{$student}%")))
            ->orderByDesc('report_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('teacher.daily-reports.index', [
            'reports' => $reports,
            'majors' => Major::query()->orderBy('code')->get(),
        ]);
    }

    public function show(Request $request, DailyReport $dailyReport): View
    {
        Gate::forUser($request->user())->authorize('view', $dailyReport);

        return view('teacher.daily-reports.show', [
            'report' => $dailyReport->load([
                'studentProfile.user.major',
            ]),
        ]);
    }
}
