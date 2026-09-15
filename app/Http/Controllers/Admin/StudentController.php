<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SoftDeleteStudent;
use App\Actions\UpdateStudentByAdmin;
use App\Actions\UpdateStudentPklPlace;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\Major;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'major' => ['nullable', 'integer', 'exists:majors,id'],
            'status' => ['nullable', 'in:pending,active,suspended'],
        ]);

        $students = User::query()
            ->where('role', Role::Student)
            ->with(['major', 'studentProfile'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['major'] ?? null, fn ($query, $majorId) => $query->where('major_id', $majorId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'majors' => Major::query()->orderBy('code')->get(),
        ]);
    }

    public function show(User $student): View
    {
        Gate::authorize('view', $student);
        $student->load(['major', 'studentProfile']);

        return view('admin.students.show', compact('student'));
    }

    public function edit(User $student): View
    {
        Gate::authorize('update', $student);
        $student->load(['major', 'studentProfile']);

        return view('admin.students.edit', [
            'student' => $student,
            'majors' => Major::query()->orderBy('code')->get(),
        ]);
    }

    public function update(UpdateStudentRequest $request, User $student, UpdateStudentByAdmin $updateStudent, UpdateStudentPklPlace $updateStudentPklPlace): RedirectResponse
    {
        $updateStudent->handle($request->user(), $student, $request->validated(), $updateStudentPklPlace);

        return to_route('admin.students.show', $student)->with('status', 'Data murid berhasil diperbarui.');
    }

    public function destroy(Request $request, User $student, SoftDeleteStudent $deleteStudent): RedirectResponse
    {
        $deleteStudent->handle($request->user(), $student);

        return to_route('admin.students.index')->with('status', 'Akun murid dihapus dan riwayatnya disimpan untuk audit.');
    }
}
