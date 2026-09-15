<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateTeacher;
use App\Actions\GuardStaffChange;
use App\Actions\SoftDeleteTeacher;
use App\Actions\UpdateTeacher;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,suspended'],
        ]);

        $teachers = User::query()
            ->whereIn('role', [Role::Teacher, Role::Admin])->where('is_protected_admin', false)
            ->with('teacherProfile')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        Gate::authorize('createTeacher', User::class);

        return view('admin.teachers.form');
    }

    public function store(StoreTeacherRequest $request, CreateTeacher $createTeacher): RedirectResponse
    {
        $teacher = $createTeacher->handle($request->user(), $request->validated());

        return to_route('admin.teachers.show', $teacher)->with('status', 'Akun guru berhasil dibuat.');
    }

    public function show(User $teacher): View
    {
        $this->ensureTeacher($teacher);
        Gate::authorize('viewTeacher', $teacher);
        $teacher->load('teacherProfile');

        return view('admin.teachers.show', compact('teacher'));
    }

    public function edit(User $teacher): View
    {
        $this->ensureTeacher($teacher);
        Gate::authorize('updateTeacher', $teacher);

        return view('admin.teachers.form', compact('teacher'));
    }

    public function update(UpdateTeacherRequest $request, User $teacher, UpdateTeacher $updateTeacher, GuardStaffChange $guard): RedirectResponse
    {
        $this->ensureTeacher($teacher);
        $teacher = $updateTeacher->handle($request->user(), $teacher, $request->validated(), $guard);

        if (! $request->user()->fresh()->isAdmin()) {
            return to_route('account');
        }

        return to_route('admin.teachers.show', $teacher)->with('status', 'Akun staf berhasil diperbarui.');
    }

    public function destroy(Request $request, User $teacher, SoftDeleteTeacher $deleteTeacher, GuardStaffChange $guard): RedirectResponse
    {
        $this->ensureTeacher($teacher);
        $deleteTeacher->handle($request->user(), $teacher, $guard);

        return to_route('admin.teachers.index')->with('status', 'Akun guru dihapus dan riwayatnya tetap tersimpan.');
    }

    private function ensureTeacher(User $teacher): void
    {
        abort_unless(in_array($teacher->role, [Role::Teacher, Role::Admin], true), 404);
    }
}
