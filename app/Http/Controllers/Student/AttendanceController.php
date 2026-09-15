<?php

namespace App\Http\Controllers\Student;

use App\Actions\CheckInStudent;
use App\Actions\CheckOutStudent;
use App\Enums\AttendanceNotificationPhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreAttendanceRequest;
use App\Jobs\SendAttendanceTelegramNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $studentProfile = $request->user()->studentProfile()->firstOrFail();

        return view('student.attendance', [
            'attendance' => $studentProfile->attendances()->whereDate('attendance_date', today())->first(),
        ]);
    }

    public function checkIn(StoreAttendanceRequest $request, CheckInStudent $checkIn): RedirectResponse
    {
        $attendance = $checkIn->handle($request->user(), $request->validated());
        $this->dispatchTelegramNotification($attendance->id, AttendanceNotificationPhase::CheckIn);

        return to_route('student.attendance')->with('status', 'Absen masuk berhasil dicatat.');
    }

    public function checkOut(StoreAttendanceRequest $request, CheckOutStudent $checkOut): RedirectResponse
    {
        $attendance = $checkOut->handle($request->user(), $request->validated());
        $this->dispatchTelegramNotification($attendance->id, AttendanceNotificationPhase::CheckOut);

        return to_route('student.attendance')->with('status', 'Absen pulang berhasil dicatat.');
    }

    private function dispatchTelegramNotification(int $attendanceId, AttendanceNotificationPhase $phase): void
    {
        try {
            SendAttendanceTelegramNotification::dispatch($attendanceId, $phase);
        } catch (Throwable $exception) {
            Log::warning('Telegram attendance notification could not be queued.', [
                'attendance_id' => $attendanceId,
                'phase' => $phase->value,
                'exception_type' => $exception::class,
            ]);
        }
    }
}
