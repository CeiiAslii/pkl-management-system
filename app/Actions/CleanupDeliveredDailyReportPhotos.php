<?php

namespace App\Actions;

use App\Models\DailyReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupDeliveredDailyReportPhotos
{
    public function handle(): int
    {
        $cleaned = 0;

        DailyReport::query()
            ->whereDate('report_date', '<', today())
            ->whereNotNull('photo_telegram_sent_at')
            ->where(fn ($query) => $query
                ->whereNotNull('activity_photo_path')
                ->orWhereNotNull('activity_photo_original_path'))
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($reports) use (&$cleaned): void {
                foreach ($reports as $report) {
                    DB::transaction(function () use ($report, &$cleaned): void {
                        $lockedReport = DailyReport::query()->lockForUpdate()->find($report->id);

                        if ($lockedReport === null
                            || ! $lockedReport->report_date->isBefore(today())
                            || $lockedReport->photo_telegram_sent_at === null) {
                            return;
                        }

                        Storage::disk('local')->delete(array_filter([
                            $lockedReport->activity_photo_path,
                            $lockedReport->activity_photo_original_path,
                        ]));
                        $lockedReport->forceFill([
                            'activity_photo_path' => null,
                            'activity_photo_original_path' => null,
                        ])->save();
                        $cleaned++;
                    });
                }
            });

        return $cleaned;
    }
}
