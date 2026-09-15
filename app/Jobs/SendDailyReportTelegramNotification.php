<?php

namespace App\Jobs;

use App\Enums\TelegramDestination;
use App\Models\DailyReport;
use App\Services\TelegramClient;
use App\Services\TelegramDestinationConfig;
use App\Services\TelegramMessageFormatter;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendDailyReportTelegramNotification implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 20;

    public bool $failOnTimeout = true;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $dailyReportId, public ?string $photoDeliveryToken = null) {}

    public function handle(
        TelegramClient $telegram,
        TelegramDestinationConfig $destinationConfig,
        TelegramMessageFormatter $formatter,
    ): void {
        $dailyReport = DailyReport::query()
            ->with([
                'studentProfile.user.major',
            ])
            ->find($this->dailyReportId);

        if ($dailyReport === null) {
            Log::warning('Telegram daily report notification skipped because the report is unavailable.', [
                'daily_report_id' => $this->dailyReportId,
            ]);

            return;
        }

        $majorCode = $formatter->majorCode($dailyReport->studentProfile);
        $destination = TelegramDestination::fromMajorCode($majorCode);
        $chatId = $destinationConfig->chatId();
        $threadId = $destination === null ? null : $destinationConfig->threadId($destination);

        if ($destination === null || $chatId === null || $threadId === null) {
            Log::warning('Telegram daily report notification skipped because its major destination is unavailable.', [
                'daily_report_id' => $dailyReport->id,
                'major_code' => $majorCode,
            ]);

            return;
        }

        $message = $this->message($dailyReport, $formatter);
        $photoPath = $this->originalPhotoPath($dailyReport);

        if ($photoPath !== null && Storage::disk('local')->exists($photoPath)) {
            $telegram->sendDocument($chatId, $threadId, $message, $photoPath);
            DailyReport::query()
                ->whereKey($dailyReport->id)
                ->where('photo_delivery_token', $this->photoDeliveryToken)
                ->update(['photo_telegram_sent_at' => now()]);

            return;
        }

        if ($photoPath !== null) {
            Log::warning('Telegram daily report photo is unavailable; sending text instead.', [
                'daily_report_id' => $dailyReport->id,
            ]);
        }

        $telegram->sendMessage($chatId, $threadId, $message);
    }

    protected function message(DailyReport $report, TelegramMessageFormatter $formatter): string
    {
        return $formatter->dailyReport($report);
    }

    protected function originalPhotoPath(DailyReport $report): ?string
    {
        return $report->photo_delivery_token === $this->photoDeliveryToken
            ? $report->activity_photo_original_path
            : null;
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Telegram daily report notification failed after all attempts.', [
            'daily_report_id' => $this->dailyReportId,
            'exception_type' => $exception === null ? null : $exception::class,
        ]);
    }
}
