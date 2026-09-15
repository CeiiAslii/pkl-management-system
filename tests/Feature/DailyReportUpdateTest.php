<?php

namespace Tests\Feature;

use App\Jobs\SendDailyReportTelegramNotification;
use App\Jobs\SendDailyReportUpdatedTelegramNotification;
use App\Models\DailyReport;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\TelegramClient;
use App\Services\TelegramDestinationConfig;
use App\Services\TelegramMessageFormatter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DailyReportUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_real_edit_queues_only_an_update_with_before_and_after_text(): void
    {
        Queue::fake();
        $report = $this->report();
        $this->actingAs($report->studentProfile->user)->patch(route('student.daily-reports.update', $report), $this->data($report, 'Kegiatan diperbarui'))
            ->assertSessionHasNoErrors()->assertRedirectToRoute('student.daily-reports.index');
        $this->assertSame('Kegiatan diperbarui', $report->fresh()->activity_description);
        Queue::assertPushed(SendDailyReportUpdatedTelegramNotification::class, fn ($job) => str_contains($job->updateMessage, 'Kegiatan awal') && str_contains($job->updateMessage, 'Kegiatan diperbarui') && ! str_contains($job->updateMessage, 'Foto:'));
        Queue::assertNotPushed(SendDailyReportTelegramNotification::class);
    }

    public function test_unchanged_submission_does_not_update_timestamp_or_notify(): void
    {
        Queue::fake();
        $report = $this->report();
        $timestamp = $report->updated_at;
        $this->travel(5)->minutes();
        $this->actingAs($report->studentProfile->user)->patch(route('student.daily-reports.update', $report), $this->data($report))
            ->assertSessionHasNoErrors();
        $this->assertTrue($timestamp->equalTo($report->fresh()->updated_at));
        Queue::assertNothingPushed();
    }

    public function test_creating_a_report_never_dispatches_an_edit_notification(): void
    {
        Queue::fake();
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->actingAs($profile->user)->post(route('student.daily-reports.store'), ['report_date' => '2026-09-12', 'activity_description' => 'Laporan baru'])->assertSessionHasNoErrors();
        Queue::assertPushed(SendDailyReportTelegramNotification::class, 1);
        Queue::assertNotPushed(SendDailyReportUpdatedTelegramNotification::class);
    }

    #[TestWith(['add', 'Ditambahkan'])]
    #[TestWith(['replace', 'Diganti'])]
    #[TestWith(['remove', 'Dihapus'])]
    public function test_photo_changes_are_private_and_described_correctly(string $operation, string $label): void
    {
        Queue::fake();
        Storage::fake('local');
        $report = $this->report();
        if ($operation !== 'add') {
            Storage::disk('local')->put('daily-report-photos/old.png', 'old-photo');
            Storage::disk('local')->put('daily-report-originals/old.png', 'old-photo');
            $report->update([
                'activity_photo_path' => 'daily-report-photos/old.png',
                'activity_photo_original_path' => 'daily-report-originals/old.png',
                'had_photo' => true,
            ]);
        }
        $data = $this->data($report);
        $data[$operation === 'remove' ? 'remove_photo' : 'activity_photo'] = $operation === 'remove' ? true : $this->photo();
        $this->actingAs($report->studentProfile->user)->patch(route('student.daily-reports.update', $report), $data)->assertSessionHasNoErrors();
        Queue::assertPushed(SendDailyReportUpdatedTelegramNotification::class, fn ($job) => str_contains($job->updateMessage, "Foto:\n{$label}") && ! str_contains($job->updateMessage, 'Sebelumnya:') && ! str_contains($job->updateMessage, 'daily-report-photos/'));
        if ($operation === 'remove') {
            $this->assertNull($report->fresh()->activity_photo_path);
        } else {
            Storage::disk('local')->assertExists($report->fresh()->activity_photo_path);
            Storage::disk('local')->assertExists($report->fresh()->activity_photo_original_path);
            $this->assertMatchesRegularExpression('/^daily-report-photos\/[0-9a-f-]+\.jpg$/', $report->fresh()->activity_photo_path);
        }
        $this->assertTrue($report->fresh()->had_photo);
        Storage::disk('local')->assertMissing('daily-report-photos/old.png');
        Storage::disk('local')->assertMissing('daily-report-originals/old.png');
    }

    public function test_uploading_identical_photo_is_not_a_real_edit(): void
    {
        Queue::fake();
        Storage::fake('local');
        $report = $this->report();
        Storage::disk('local')->put('daily-report-photos/original.jpg', 'web-copy');
        Storage::disk('local')->put('daily-report-originals/original.png', $this->photo()->getContent());
        $report->update([
            'activity_photo_path' => 'daily-report-photos/original.jpg',
            'activity_photo_original_path' => 'daily-report-originals/original.png',
            'had_photo' => true,
        ]);
        $this->actingAs($report->studentProfile->user)->patch(route('student.daily-reports.update', $report), [...$this->data($report), 'activity_photo' => $this->photo()])->assertSessionHasNoErrors();
        Queue::assertNothingPushed();
        $this->assertSame('daily-report-photos/original.jpg', $report->fresh()->activity_photo_path);
    }

    public function test_queue_failure_does_not_rollback_the_report_edit(): void
    {
        $report = $this->report();
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        $this->actingAs($report->studentProfile->user)->patch(route('student.daily-reports.update', $report), $this->data($report, 'Tetap disimpan'))->assertSessionHasNoErrors();
        $this->assertSame('Tetap disimpan', $report->fresh()->activity_description);
    }

    #[TestWith(['TKJ', 102])]
    #[TestWith(['TSM', 103])]
    #[TestWith(['DPB', 104])]
    #[TestWith(['MP', 105])]
    public function test_update_job_uses_server_major_thread_and_safe_plain_text(string $majorCode, int $thread): void
    {
        $this->configureTelegram();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $report = $this->report($majorCode);
        $message = app(TelegramMessageFormatter::class)->dailyReportUpdated($report, ['activity_description' => ['before' => 'Awal', 'after' => '<b>Baru & aman</b>']]);
        $this->runJob(new SendDailyReportUpdatedTelegramNotification($report->id, $message, null));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage') && (int) $request['message_thread_id'] === $thread
            && str_contains($request['text'], '✏️ LAPORAN PKL DIPERBARUI') && str_contains($request['text'], '<b>Baru & aman</b>') && ! isset($request['parse_mode']));
    }

    public function test_updated_photo_is_sent_with_caption_but_removed_photo_uses_text(): void
    {
        $this->configureTelegram();
        Storage::fake('local');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $report = $this->report();
        Storage::disk('local')->put('daily-report-originals/current.png', $this->photo()->getContent());
        $report->update([
            'activity_photo_original_path' => 'daily-report-originals/current.png',
            'activity_photo_path' => 'daily-report-photos/current.jpg',
            'photo_delivery_token' => 'current-photo-token',
            'had_photo' => true,
        ]);
        $this->runJob(new SendDailyReportUpdatedTelegramNotification($report->id, '✏️ LAPORAN PKL DIPERBARUI Foto: Ditambahkan', 'current-photo-token'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendDocument'));
        $report->update(['activity_photo_path' => null, 'activity_photo_original_path' => null, 'photo_delivery_token' => null]);
        $this->runJob(new SendDailyReportUpdatedTelegramNotification($report->id, '✏️ LAPORAN PKL DIPERBARUI Foto: Dihapus', null));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage') && str_contains($request['text'], 'Dihapus'));
    }

    public function test_edit_rejects_spoofed_routing_and_unauthorized_ownership(): void
    {
        Queue::fake();
        $report = $this->report();
        $this->actingAs($report->studentProfile->user)->patchJson(route('student.daily-reports.update', $report), [...$this->data($report, 'Invalid'), 'thread_id' => 666])->assertUnprocessable()->assertJsonValidationErrors('thread_id');
        $this->actingAs(User::factory()->approvedStudent()->create())->patchJson(route('student.daily-reports.update', $report), $this->data($report, 'Stolen'))->assertForbidden();
        $this->assertSame('Kegiatan awal', $report->fresh()->activity_description);
        Queue::assertNothingPushed();
    }

    private function configureTelegram(): void
    {
        config(['services.telegram.bot_token' => 'test-only-token', 'services.telegram.chat_id' => '-1001234567890', 'services.telegram.threads.tkj' => 102, 'services.telegram.threads.tsm' => 103, 'services.telegram.threads.dpb' => 104, 'services.telegram.threads.mp' => 105]);
    }

    private function runJob(SendDailyReportUpdatedTelegramNotification $job): void
    {
        $job->handle(app(TelegramClient::class), app(TelegramDestinationConfig::class), app(TelegramMessageFormatter::class));
    }

    private function report(string $majorCode = 'TKJ'): DailyReport
    {
        $major = Major::factory()->create(['code' => $majorCode]);
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent()->for($major))->create();

        return DailyReport::factory()->for($profile)->create(['activity_description' => 'Kegiatan awal']);
    }

    /** @return array<string, string> */
    private function data(DailyReport $report, ?string $activity = null): array
    {
        return ['report_date' => $report->report_date->toDateString(), 'activity_description' => $activity ?? $report->activity_description];
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('image.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));
    }
}
