<?php

namespace App\Jobs;

use App\Enums\AttendanceNotificationPhase;
use App\Enums\TelegramDestination;
use App\Models\Attendance;
use App\Services\TelegramClient;
use App\Services\TelegramDestinationConfig;
use App\Services\TelegramMessageFormatter;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendAttendanceTelegramNotification implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 20;

    public bool $failOnTimeout = true;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $attendanceId,
        public AttendanceNotificationPhase $phase,
    ) {}

    public function handle(
        TelegramClient $telegram,
        TelegramDestinationConfig $destinationConfig,
        TelegramMessageFormatter $formatter,
    ): void {
        $attendance = Attendance::query()
            ->with([
                'studentProfile.user.major',
            ])
            ->find($this->attendanceId);

        if ($attendance === null) {
            Log::warning('Telegram attendance notification skipped because attendance is unavailable.', [
                'attendance_id' => $this->attendanceId,
                'phase' => $this->phase->value,
            ]);

            return;
        }

        $chatId = $destinationConfig->chatId();
        $threadId = $destinationConfig->threadId(TelegramDestination::Absensi);

        if ($chatId === null || $threadId === null) {
            Log::warning('Telegram attendance notification skipped because its destination is not configured.', [
                'attendance_id' => $attendance->id,
                'phase' => $this->phase->value,
            ]);

            return;
        }

        $message = $formatter->attendance($attendance, $this->phase);
        $selfiePath = $this->phase === AttendanceNotificationPhase::CheckIn
            ? $attendance->check_in_selfie_path
            : $attendance->check_out_selfie_path;

        if ($selfiePath !== null && Storage::disk('local')->exists($selfiePath)) {
            $telegram->sendPhoto($chatId, $threadId, $message, $selfiePath);

            return;
        }

        if ($selfiePath !== null) {
            Log::warning('Telegram attendance selfie is unavailable; sending text instead.', [
                'attendance_id' => $attendance->id,
                'phase' => $this->phase->value,
            ]);
        }

        $telegram->sendMessage($chatId, $threadId, $message);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Telegram attendance notification failed after all attempts.', [
            'attendance_id' => $this->attendanceId,
            'phase' => $this->phase->value,
            'exception_type' => $exception === null ? null : $exception::class,
        ]);
    }
}
