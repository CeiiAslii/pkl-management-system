<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileController extends Controller
{
    public function profilePhoto(Request $request): StreamedResponse
    {
        $profilePhotoPath = $request->user()->studentProfile?->profile_photo_path;
        abort_if($profilePhotoPath === null || ! Storage::disk('local')->exists($profilePhotoPath), 404);

        return Storage::disk('local')->response($profilePhotoPath);
    }

    public function dailyReportPhoto(DailyReport $dailyReport): StreamedResponse
    {
        Gate::authorize('view', $dailyReport);
        abort_if($dailyReport->activity_photo_path === null || ! Storage::disk('local')->exists($dailyReport->activity_photo_path), 404);

        return Storage::disk('local')->response($dailyReport->activity_photo_path);
    }

    public function leaveRequestFile(LeaveRequest $leaveRequest): StreamedResponse
    {
        Gate::authorize('view', $leaveRequest);
        abort_if($leaveRequest->supporting_file_path === null || ! Storage::disk('local')->exists($leaveRequest->supporting_file_path), 404);

        return Storage::disk('local')->response($leaveRequest->supporting_file_path);
    }
}
