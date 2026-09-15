<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherNoteRequest;
use App\Http\Requests\Teacher\UpdateTeacherNoteRequest;
use App\Models\Major;
use App\Models\TeacherNote;
use App\Support\ActiveStudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeacherNoteController extends Controller
{
    public function index(Request $request, ActiveStudentScope $activeStudents): View
    {
        Gate::authorize('viewAny', TeacherNote::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'major' => ['nullable', 'integer', 'exists:majors,id'],
        ]);

        $filteredStudents = $activeStudents->query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")))
            ->when($filters['major'] ?? null, fn ($query, $major) => $query->whereHas('user', fn ($query) => $query->where('major_id', $major)));

        return view('teacher.notes.index', [
            'notes' => TeacherNote::query()
                ->whereIn('student_profile_id', (clone $filteredStudents)->select('id'))
                ->with(['studentProfile.user.major', 'teacherProfile.user'])
                ->latest()->paginate(15)->withQueryString(),
            'students' => (clone $filteredStudents)->with(['user.major'])->orderBy('id')->get(),
            'majors' => Major::query()->orderBy('code')->get(),
        ]);
    }

    public function store(StoreTeacherNoteRequest $request, ActiveStudentScope $activeStudents): RedirectResponse
    {
        $student = $activeStudents->query()->whereKey($request->integer('student_profile_id'))->firstOrFail();
        $activeStudents->teacherProfile($request->user())->notes()->create([
            'student_profile_id' => $student->id,
            'content' => $request->validated('content'),
        ]);

        return to_route('teacher.notes.index')->with('status', 'Catatan murid berhasil ditambahkan.');
    }

    public function edit(TeacherNote $teacherNote): View
    {
        Gate::authorize('update', $teacherNote);

        return view('teacher.notes.edit', [
            'teacherNote' => $teacherNote->load(['studentProfile.user.major']),
        ]);
    }

    public function update(UpdateTeacherNoteRequest $request, TeacherNote $teacherNote): RedirectResponse
    {
        $teacherNote->update($request->safe()->only(['content']));

        return to_route('teacher.notes.index')->with('status', 'Catatan murid berhasil diperbarui.');
    }

    public function destroy(Request $request, TeacherNote $teacherNote): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('delete', $teacherNote);
        $teacherNote->delete();

        return to_route('teacher.notes.index')->with('status', 'Catatan murid berhasil dihapus.');
    }
}
