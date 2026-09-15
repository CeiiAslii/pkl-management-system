<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendancePrivateFileController extends Controller
{
    public function __invoke(Attendance $attendance, string $phase): StreamedResponse
    {
        Gate::authorize('view', $attendance);
        $path = match ($phase) {
            'check-in' => $attendance->check_in_selfie_path,
            'check-out' => $attendance->check_out_selfie_path,
            default => null,
        };
        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
