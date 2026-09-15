<?php

namespace App\Actions;

use App\Http\Requests\Student\UpdateDailyReportRequest;
use App\Jobs\SendDailyReportUpdatedTelegramNotification;
use App\Models\DailyReport;
use App\Services\TelegramMessageFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateDailyReport
{
    public function __construct(private StoreDailyReportPhoto $storePhoto) {}

    public function handle(UpdateDailyReportRequest $request, DailyReport $report, TelegramMessageFormatter $formatter): void
    {
        $newPhoto = null;
        $oldFiles = [];
        $message = null;
        try {
            DB::transaction(function () use ($request, &$report, $formatter, &$newPhoto, &$oldFiles, &$message): void {
                $report = DailyReport::query()->lockForUpdate()->findOrFail($report->id);
                Gate::authorize('update', $report);
                $oldFiles = array_filter([
                    $report->activity_photo_path,
                    $report->activity_photo_original_path,
                ]);
                $before = ['report_date' => $report->report_date->toDateString(), 'activity_description' => $report->activity_description];
                $report->fill($request->safe()->only(['report_date', 'activity_description']));
                $changes = [];
                foreach ($before as $field => $value) {
                    if ($report->isDirty($field)) {
                        $changes[$field] = ['before' => $value, 'after' => $field === 'report_date' ? $report->report_date->toDateString() : $report->$field];
                    }
                }
                if ($request->hasFile('activity_photo')) {
                    $samePhoto = $report->activity_photo_original_path !== null
                        && Storage::disk('local')->exists($report->activity_photo_original_path)
                        && hash('sha256', Storage::disk('local')->get($report->activity_photo_original_path)) === hash_file('sha256', $request->file('activity_photo')->getRealPath());
                    if (! $samePhoto) {
                        $newPhoto = $this->storePhoto->handle($request->file('activity_photo'));
                        $report->activity_photo_path = $newPhoto['web_path'];
                        $report->activity_photo_original_path = $newPhoto['original_path'];
                        $report->had_photo = true;
                        $report->photo_delivery_token = $newPhoto['delivery_token'];
                        $report->photo_telegram_sent_at = null;
                        $changes['photo'] = $oldFiles === [] ? 'Ditambahkan' : 'Diganti';
                    }
                } elseif ($request->boolean('remove_photo') && $oldFiles !== []) {
                    $report->activity_photo_path = null;
                    $report->activity_photo_original_path = null;
                    $report->photo_delivery_token = null;
                    $report->photo_telegram_sent_at = null;
                    $changes['photo'] = 'Dihapus';
                }
                if ($changes !== []) {
                    $report->save();
                    $report->load('studentProfile.user.major');
                    $message = $formatter->dailyReportUpdated($report, $changes);
                }
            });
        } catch (Throwable $exception) {
            if ($newPhoto !== null) {
                Storage::disk('local')->delete([$newPhoto['web_path'], $newPhoto['original_path']]);
            }
            throw $exception;
        }

        if ($newPhoto !== null || ($request->boolean('remove_photo') && $oldFiles !== [])) {
            Storage::disk('local')->delete($oldFiles);
        }
        if ($message !== null) {
            try {
                SendDailyReportUpdatedTelegramNotification::dispatch(
                    $report->id,
                    $message,
                    $newPhoto['delivery_token'] ?? null,
                );
            } catch (Throwable $exception) {
                Log::warning('Telegram report update could not be queued.', ['exception_type' => $exception::class]);
            }
        }
    }
}
