<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\ReviewLeaveRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ReviewLeaveRequestRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;

class LeaveRequestReviewController extends Controller
{
    public function __invoke(ReviewLeaveRequestRequest $request, LeaveRequest $leaveRequest, ReviewLeaveRequest $reviewLeaveRequest): RedirectResponse
    {
        $reviewLeaveRequest->handle($request->user(), $leaveRequest, $request->validated());

        return back()->with('status', 'Status izin/sakit berhasil diperbarui.');
    }
}
