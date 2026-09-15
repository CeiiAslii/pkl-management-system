<?php

namespace App\Http\Controllers;

use App\Actions\ApproveStudent;
use App\Actions\RejectStudent;
use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentApprovalController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.student-approvals', [
            'students' => User::with(['major', 'studentProfile'])
                ->where('role', Role::Student)->where('status', AccountStatus::Pending)
                ->orderBy('id')->paginate(20),
        ]);
    }

    public function store(Request $request, User $student, ApproveStudent $approve): RedirectResponse
    {
        $approve->handle($request->user(), $student);

        return to_route('student-approvals.index')->with('status', 'Student approved.');
    }

    public function reject(Request $request, User $student, RejectStudent $reject): RedirectResponse
    {
        $reject->handle($request->user(), $student);

        return to_route('student-approvals.index')->with('status', 'Pendaftaran murid ditolak.');
    }
}
