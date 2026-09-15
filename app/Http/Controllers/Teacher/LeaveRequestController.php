<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Support\ActiveStudentScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request, ActiveStudentScope $activeStudents): View
    {
        $filters = $request->validate([
            'major' => ['nullable', 'integer', 'exists:majors,id'],
            'student' => ['nullable', 'string', 'max:100'],
        ]);
        $studentIds = $activeStudents->query()->select('id');

        $requests = LeaveRequest::query()
            ->whereIn('student_profile_id', $studentIds)
            ->with(['studentProfile.user.major', 'reviewer.user'])
            ->when($filters['major'] ?? null, fn ($query, $major) => $query->whereHas('studentProfile.user', fn ($query) => $query->where('major_id', $major)))
            ->when($filters['student'] ?? null, fn ($query, $student) => $query->whereHas('studentProfile.user', fn ($query) => $query->where('name', 'like', "%{$student}%")))
            ->orderByDesc('requested_for')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('teacher.leave-requests.index', [
            'requests' => $requests,
            'majors' => Major::query()->orderBy('code')->get(),
        ]);
    }
}
