<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Support\ActiveStudentScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __invoke(Request $request, ActiveStudentScope $activeStudents): View
    {
        $filters = $request->validate([
            'major' => ['nullable', 'integer', 'exists:majors,id'],
            'student' => ['nullable', 'string', 'max:100'],
        ]);

        $students = $activeStudents->query()
            ->with([
                'user.major',
                'attendances' => fn ($query) => $query->whereDate('attendance_date', today()),
            ])
            ->when($filters['major'] ?? null, fn ($query, $major) => $query->whereHas('user', fn ($query) => $query->where('major_id', $major)))
            ->when($filters['student'] ?? null, fn ($query, $student) => $query->whereHas('user', fn ($query) => $query->where('name', 'like', "%{$student}%")))
            ->orderBy('id')->paginate(15)->withQueryString();

        return view('teacher.attendance', [
            'students' => $students,
            'majors' => Major::query()->orderBy('code')->get(),
        ]);
    }
}
