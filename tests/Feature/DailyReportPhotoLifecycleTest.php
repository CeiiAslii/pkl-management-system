<?php

namespace Tests\Feature;

use App\Exceptions\TelegramException;
use App\Jobs\SendDailyReportTelegramNotification;
use App\Models\DailyReport;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\TelegramClient;
use App\Services\TelegramDestinationConfig;
use App\Services\TelegramMessageFormatter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DailyReportPhotoLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_large_upload_keeps_exact_original_and_creates_a_private_compressed_web_copy(): void
    {
        Storage::fake('local');
        Queue::fake([SendDailyReportTelegramNotification::class]);
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $sourcePath = tempnam(sys_get_temp_dir(), 'pkl-large-photo-');
        $this->assertIsString($sourcePath);
        (new Process(['magick', '-size', '2400x1800', 'plasma:fractal', '-quality', '96', 'jpeg:'.$sourcePath]))->mustRun();
        $sourceHash = hash_file('sha256', $sourcePath);

        try {
            $photo = new UploadedFile($sourcePath, 'kegiatan-asli.jpg', 'image/jpeg', null, true);
            $this->actingAs($profile->user)->post(route('student.daily-reports.store'), [
                'report_date' => today()->toDateString(),
                'activity_description' => 'Dokumentasi pekerjaan berukuran besar.',
                'activity_photo' => $photo,
            ])->assertRedirectToRoute('student.daily-reports.index');
        } finally {
            @unlink($sourcePath);
        }

        $report = DailyReport::query()->sole();
        Storage::disk('local')->assertExists($report->activity_photo_path);
        Storage::disk('local')->assertExists($report->activity_photo_original_path);
        $this->assertSame($sourceHash, hash_file('sha256', Storage::disk('local')->path($report->activity_photo_original_path)));
        $this->assertLessThanOrEqual(512_000, Storage::disk('local')->size($report->activity_photo_path));
        $dimensions = new Process(['magick', 'identify', '-format', '%w %h', Storage::disk('local')->path($report->activity_photo_path)]);
        $dimensions->mustRun();
        [$width, $height] = array_map('intval', explode(' ', $dimensions->getOutput()));
        $this->assertLessThanOrEqual(1600, max($width, $height));
        $this->assertTrue($report->had_photo);
        Queue::assertPushed(SendDailyReportTelegramNotification::class, fn (SendDailyReportTelegramNotification $job): bool => $job->photoDeliveryToken === $report->photo_delivery_token);
    }

    public function test_cleanup_removes_only_old_successfully_delivered_photo_files_and_keeps_records(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-09-14 00:05:00');
        $eligible = $this->reportWithPhoto('2026-09-13', 'eligible', now()->subHour());
        $failed = $this->reportWithPhoto('2026-09-13', 'failed');
        $today = $this->reportWithPhoto('2026-09-14', 'today', now());

        $this->artisan('pkl:cleanup-daily-report-photos')->assertSuccessful();

        Storage::disk('local')->assertMissing(['daily-report-photos/eligible.jpg', 'daily-report-originals/eligible.jpg']);
        Storage::disk('local')->assertExists('daily-report-photos/failed.jpg');
        Storage::disk('local')->assertExists('daily-report-originals/failed.jpg');
        Storage::disk('local')->assertExists('daily-report-photos/today.jpg');
        Storage::disk('local')->assertExists('daily-report-originals/today.jpg');
        $this->assertNull($eligible->fresh()->activity_photo_path);
        $this->assertTrue($eligible->fresh()->had_photo);
        $this->assertNotNull($failed->fresh()->activity_photo_path);
        $this->assertNotNull($today->fresh()->activity_photo_path);
        $this->assertDatabaseCount('daily_reports', 3);
    }

    public function test_failed_telegram_delivery_does_not_make_old_original_eligible_for_cleanup(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-09-14 00:05:00');
        $report = $this->reportWithPhoto('2026-09-13', 'delivery-failed');
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '-100123',
            'services.telegram.threads.tkj' => 102,
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);

        try {
            (new SendDailyReportTelegramNotification($report->id, $report->photo_delivery_token))->handle(
                app(TelegramClient::class),
                app(TelegramDestinationConfig::class),
                app(TelegramMessageFormatter::class),
            );
            $this->fail('TelegramException was not thrown.');
        } catch (TelegramException) {
            $this->assertNull($report->fresh()->photo_telegram_sent_at);
        }

        $this->artisan('pkl:cleanup-daily-report-photos')->assertSuccessful();

        Storage::disk('local')->assertExists($report->activity_photo_path);
        Storage::disk('local')->assertExists($report->activity_photo_original_path);
    }

    private function reportWithPhoto(string $date, string $name, mixed $sentAt = null): DailyReport
    {
        $major = Major::query()->firstOrCreate(
            ['code' => 'TKJ'],
            ['name' => 'Teknik Komputer dan Jaringan'],
        );
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent()->for($major))->create();
        $webPath = "daily-report-photos/{$name}.jpg";
        $originalPath = "daily-report-originals/{$name}.jpg";
        Storage::disk('local')->put($webPath, 'compressed');
        Storage::disk('local')->put($originalPath, 'original');

        return DailyReport::factory()->for($profile)->create([
            'report_date' => $date,
            'activity_photo_path' => $webPath,
            'activity_photo_original_path' => $originalPath,
            'had_photo' => true,
            'photo_delivery_token' => "token-{$name}",
            'photo_telegram_sent_at' => $sentAt,
        ]);
    }
}
