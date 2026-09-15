<?php

namespace App\Console\Commands;

use App\Actions\CleanupDeliveredDailyReportPhotos;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pkl:cleanup-daily-report-photos')]
#[Description('Hapus berkas foto laporan lama yang telah berhasil dikirim ke Telegram')]
class CleanupDailyReportPhotos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CleanupDeliveredDailyReportPhotos $cleanup): int
    {
        $count = $cleanup->handle();
        $this->info("{$count} laporan lama dibersihkan.");

        return self::SUCCESS;
    }
}
