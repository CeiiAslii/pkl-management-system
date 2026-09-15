<?php

namespace App\Http\Controllers\Student;

use App\Actions\StoreStudentPrivateFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        return view('student.leave-requests.index', [
            'leaveRequests' => $request->user()->studentProfile->leaveRequests()->latest('requested_for')->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', LeaveRequest::class);

        return view('student.leave-requests.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveRequestRequest $request, StoreStudentPrivateFile $storePrivateFile): RedirectResponse
    {
        $attributes = $request->safe()->only(['requested_for', 'type', 'reason']);
        $supportingFilePath = null;

        if ($request->hasFile('supporting_file')) {
            $supportingFilePath = $storePrivateFile->handle($request->file('supporting_file'), 'leave-request-files');
            $attributes['supporting_file_path'] = $supportingFilePath;
        }

        try {
            $request->user()->studentProfile->leaveRequests()->create($attributes);
        } catch (Throwable $exception) {
            if ($supportingFilePath !== null) {
                Storage::disk('local')->delete($supportingFilePath);
            }

            throw $exception;
        }

        return to_route('student.leave-requests.index')->with('status', 'Pengajuan berhasil dikirim.');
    }
}
