<?php

namespace App\Jobs;

use App\Models\DailyReport;
use App\Services\TelegramMessageFormatter;

class SendDailyReportUpdatedTelegramNotification extends SendDailyReportTelegramNotification
{
    public function __construct(int $dailyReportId, public string $updateMessage, ?string $photoDeliveryToken)
    {
        parent::__construct($dailyReportId, $photoDeliveryToken);
    }

    protected function message(DailyReport $report, TelegramMessageFormatter $formatter): string
    {
        return $this->updateMessage;
    }
}
