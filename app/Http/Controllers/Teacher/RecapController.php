<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Support\ActiveStudentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecapController extends Controller
{
    public function __invoke(Request $request, ActiveStudentScope $activeStudents): View
    {
        $filters = $request->validate([
            'major' => ['nullable', 'integer', 'exists:majors,id'],
            'student' => ['nullable', 'string', 'max:100'],
            'student_profile_id' => ['nullable', 'integer', 'exists:student_profiles,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $studentQuery = $activeStudents->query()
            ->with(['user.major'])
            ->when($filters['major'] ?? null, fn ($query, $major) => $query->whereHas('user', fn ($query) => $query->where('major_id', $major)))
            ->when($filters['student'] ?? null, fn ($query, $student) => $query->whereHas('user', fn ($query) => $query->where('name', 'like', "%{$student}%")))
            ->withCount([
                'dailyReports as daily_reports_count' => fn (Builder $query) => $this->applyReportDateRange($query, $filters),
                'leaveRequests as permission_count' => fn (Builder $query) => $this->applyLeaveDateRange($query, $filters)->where('type', 'izin'),
                'leaveRequests as sick_count' => fn (Builder $query) => $this->applyLeaveDateRange($query, $filters)->where('type', 'sakit'),
            ])
            ->orderBy('id');
        $students = (clone $studentQuery)->paginate(15)->withQueryString();
        $student = null;

        if (isset($filters['student_profile_id'])) {
            $student = $activeStudents->query()
                ->with(['user.major'])
                ->whereKey($filters['student_profile_id'])->firstOrFail();

            $student->setRelation('dailyReports', $student->dailyReports()
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '<=', $date))
                ->latest('report_date')->get());
            $student->setRelation('leaveRequests', $student->leaveRequests()
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('requested_for', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('requested_for', '<=', $date))
                ->latest('requested_for')->get());
            $student->setRelation('teacherNotes', $student->teacherNotes()
                ->with('teacherProfile.user')
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
                ->latest()->get());
            $student->setRelation('attendances', $student->attendances()
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))
                ->latest('attendance_date')->get());
        }

        return view('teacher.recap', [
            'students' => $students,
            'student' => $student,
            'majors' => Major::query()->orderBy('code')->get(),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function applyReportDateRange(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate('report_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate('report_date', '<=', $date));
    }

    /** @param array<string, mixed> $filters */
    private function applyLeaveDateRange(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate('requested_for', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate('requested_for', '<=', $date));
    }
}
