<?php

namespace Tests\Feature;

use App\Enums\AttendanceNotificationPhase;
use App\Enums\TelegramDestination;
use App\Exceptions\TelegramException;
use App\Jobs\SendAttendanceTelegramNotification;
use App\Jobs\SendDailyReportTelegramNotification;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\TelegramClient;
use App\Services\TelegramDestinationConfig;
use App\Services\TelegramMessageFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TelegramIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_telegram_client_uses_configured_chat_and_thread_without_exposing_token_on_failure(): void
    {
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake([
            'api.telegram.org/*' => Http::sequence()
                ->push(['ok' => true])
                ->push(['ok' => false, 'description' => 'Ditolak']),
        ]);

        $destination = app(TelegramDestinationConfig::class);
        app(TelegramClient::class)->sendMessage($destination->chatId(), $destination->threadId(TelegramDestination::Tkj), 'Pesan aman');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === '-1001234567890'
            && (int) $request['message_thread_id'] === 102
            && $request['text'] === 'Pesan aman');

        try {
            app(TelegramClient::class)->sendMessage('-1001234567890', 102, 'Akan gagal');
            $this->fail('TelegramException was not thrown.');
        } catch (TelegramException $exception) {
            $this->assertSame('Telegram request failed.', $exception->getMessage());
            $this->assertStringNotContainsString('test-bot-token', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_telegram_client_wraps_connection_failures_without_leaking_the_token(): void
    {
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake([
            'api.telegram.org/*' => Http::failedConnection(),
        ]);

        try {
            app(TelegramClient::class)->sendMessage('-1001234567890', 102, 'Akan gagal');
            $this->fail('TelegramException was not thrown.');
        } catch (TelegramException $exception) {
            $this->assertSame('Telegram request failed.', $exception->getMessage());
            $this->assertStringNotContainsString('test-bot-token', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_attendance_check_in_and_check_out_queue_notifications_after_saving(): void
    {
        Queue::fake([SendAttendanceTelegramNotification::class]);
        Storage::fake('local');
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($student->user)->post(route('student.attendance.check-in'), [
            ...$this->attendancePayload(),
            'chat_id' => 'attacker-chat',
            'message_thread_id' => 999,
            'telegram_destination' => 'tkj',
            'major' => 'MP',
        ])->assertSessionHasErrors(['chat_id', 'message_thread_id', 'telegram_destination', 'major']);
        $this->assertDatabaseCount('attendances', 0);
        Queue::assertNotPushed(SendAttendanceTelegramNotification::class);

        $this->actingAs($student->user)->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');
        $attendance = Attendance::query()->sole();
        Queue::assertPushed(SendAttendanceTelegramNotification::class, fn (SendAttendanceTelegramNotification $job): bool => $job->attendanceId === $attendance->id
            && $job->phase === AttendanceNotificationPhase::CheckIn);

        $this->post(route('student.attendance.check-out'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');
        Queue::assertPushed(SendAttendanceTelegramNotification::class, fn (SendAttendanceTelegramNotification $job): bool => $job->attendanceId === $attendance->id
            && $job->phase === AttendanceNotificationPhase::CheckOut);
        Queue::assertPushedTimes(SendAttendanceTelegramNotification::class, 2);
        $this->assertNotNull($attendance->fresh()->check_out_at);
    }

    public function test_attendance_jobs_use_absensi_thread_private_selfies_and_trusted_database_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 23:30:00', 'UTC'));
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Storage::fake('local');
        $major = Major::factory()->create(['code' => 'TKJ']);
        $student = StudentProfile::factory()
            ->for(User::factory()->approvedStudent()->state(['name' => 'Murid Telegram', 'major_id' => $major->id]))
            ->create(['pkl_place_name' => 'PT PKL Aman']);
        $attendance = Attendance::factory()->checkedOut()->for($student)->create([
            'check_in_selfie_path' => 'attendance-selfies/private-in.png',
            'check_out_selfie_path' => 'attendance-selfies/private-out.png',
            'attendance_date' => today(),
            'check_in_at' => now()->setTime(7, 30),
            'check_out_at' => now()->setTime(16, 45),
        ]);
        Storage::disk('local')->put($attendance->check_in_selfie_path, 'check-in-image');
        Storage::disk('local')->put($attendance->check_out_selfie_path, 'check-out-image');

        $this->runAttendanceJob($attendance, AttendanceNotificationPhase::CheckIn);
        $this->runAttendanceJob($attendance, AttendanceNotificationPhase::CheckOut);

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            $caption = $this->multipartField($request, 'caption');

            return str_ends_with($request->url(), '/sendPhoto')
                && $request->hasFile('photo')
                && $this->multipartField($request, 'chat_id') === '-1001234567890'
                && $this->multipartField($request, 'message_thread_id') === '101'
                && str_contains($caption, '✅ ABSEN MASUK')
                && str_contains($caption, 'Nama: Murid Telegram')
                && str_contains($caption, 'Jurusan: TKJ')
                && str_contains($caption, 'Tanggal: 11 September 2026')
                && str_contains($caption, 'Jam Masuk: 07:30')
                && str_contains($caption, 'Tempat PKL: PT PKL Aman')
                && ! str_contains($caption, 'Kelas:')
                && ! str_contains($caption, 'Status Lokasi:')
                && ! str_contains($caption, 'attendance-selfies/');
        });
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendPhoto')
            && str_contains($this->multipartField($request, 'caption'), '🏁 ABSEN PULANG')
            && str_contains($this->multipartField($request, 'caption'), 'Jam Pulang: 16:45'));
    }

    #[TestWith(['TKJ', 102])]
    #[TestWith(['TSM', 103])]
    #[TestWith(['DPB', 104])]
    #[TestWith(['MP', 105])]
    public function test_daily_report_job_routes_each_major_to_its_configured_thread(string $majorCode, int $threadId): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-11 09:20:00', 'UTC'));
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $report = $this->dailyReportForMajor($majorCode);

        $this->runDailyReportJob($report);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && (int) $request['message_thread_id'] === $threadId
            && str_contains($request['text'], "Jurusan: {$majorCode}")
            && str_contains($request['text'], "Kegiatan:\nMenyusun laporan Telegram")
            && str_contains($request['text'], 'Waktu Kirim: 11 September 2026 17:20'));
    }

    public function test_daily_report_with_private_photo_sends_the_exact_original_as_a_document_without_leaking_storage_path(): void
    {
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Storage::fake('local');
        $report = $this->dailyReportForMajor('DPB', 'daily-report-photos/private-photo.jpg');
        Storage::disk('local')->put($report->activity_photo_path, 'compressed-web-image');
        Storage::disk('local')->put($report->activity_photo_original_path, 'byte-exact-original-image');

        $this->runDailyReportJob($report);

        Http::assertSent(function (Request $request) use ($report): bool {
            $caption = $this->multipartField($request, 'caption');

            return str_ends_with($request->url(), '/sendDocument')
                && $request->hasFile('document')
                && $this->multipartField($request, 'message_thread_id') === '104'
                && str_contains($caption, '📝 LAPORAN KEGIATAN PKL')
                && str_contains($caption, 'Tempat PKL: Tempat PKL Murid')
                && ! str_contains($caption, 'Kelas:')
                && ! str_contains($caption, $report->activity_photo_path);
        });
        $this->assertNotNull($report->fresh()->photo_telegram_sent_at);
    }

    public function test_missing_report_photo_falls_back_to_text_notification(): void
    {
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Storage::fake('local');
        $report = $this->dailyReportForMajor('TSM', 'daily-report-photos/missing.png');

        $this->runDailyReportJob($report);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/sendMessage')
            && (int) $request['message_thread_id'] === 103);
    }

    public function test_unknown_major_or_missing_thread_skips_telegram_without_losing_report(): void
    {
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['major_id' => null]))->create();
        $report = DailyReport::factory()->for($profile)->create();

        $this->runDailyReportJob($report);

        Http::assertNothingSent();
        $this->assertModelExists($report);

        $knownReport = $this->dailyReportForMajor('TKJ');
        config()->set('services.telegram.threads.tkj');
        $this->runDailyReportJob($knownReport);
        Http::assertNothingSent();
        $this->assertModelExists($knownReport);
    }

    public function test_daily_report_creation_queues_once_and_frontend_cannot_choose_telegram_destination(): void
    {
        Queue::fake([SendDailyReportTelegramNotification::class]);
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();

        $this->actingAs($student->user)->get(route('student.daily-reports.create'))
            ->assertDontSee('test-bot-token');

        $this->post(route('student.daily-reports.store'), [
            'report_date' => '2026-09-11',
            'activity_description' => 'Laporan dengan tujuan palsu.',
            'chat_id' => 'attacker-chat',
            'message_thread_id' => 999,
            'telegram_destination' => 'absensi',
            'major' => 'MP',
        ])->assertSessionHasErrors(['chat_id', 'message_thread_id', 'telegram_destination', 'major']);
        $this->assertDatabaseCount('daily_reports', 0);
        Queue::assertNotPushed(SendDailyReportTelegramNotification::class);

        $this->post(route('student.daily-reports.store'), [
            'report_date' => '2026-09-11',
            'activity_description' => 'Laporan yang sah.',
        ])->assertRedirectToRoute('student.daily-reports.index');
        $report = DailyReport::query()->sole();
        Queue::assertPushed(SendDailyReportTelegramNotification::class, fn (SendDailyReportTelegramNotification $job): bool => $job->dailyReportId === $report->id);

        $this->put(route('student.daily-reports.update', $report), [
            'report_date' => '2026-09-11',
            'activity_description' => 'Laporan diperbarui tanpa notifikasi baru.',
        ])->assertRedirectToRoute('student.daily-reports.index');
        Queue::assertPushedTimes(SendDailyReportTelegramNotification::class, 1);
    }

    public function test_telegram_api_failure_never_rolls_back_attendance_or_report_submission(): void
    {
        $this->configureTelegram();
        config()->set('queue.default', 'sync');
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 200)]);
        Storage::fake('local');
        $major = Major::factory()->create(['code' => 'TKJ']);
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['major_id' => $major->id]))->create();

        $this->actingAs($student->user)->post(route('student.attendance.check-in'), $this->attendancePayload())
            ->assertRedirectToRoute('student.attendance');
        $this->post(route('student.daily-reports.store'), [
            'report_date' => '2026-09-11',
            'activity_description' => 'Tetap tersimpan saat Telegram gagal.',
        ])->assertRedirectToRoute('student.daily-reports.index');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('daily_reports', 1);
    }

    public function test_telegram_test_command_validates_destination_and_never_prints_token(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-11 09:20:00', 'UTC'));
        $this->configureTelegram();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->artisan('pkl:telegram-test', ['destination' => 'tkj'])
            ->expectsOutput('Pesan uji berhasil dikirim ke topik TKJ.')
            ->doesntExpectOutputToContain('test-bot-token')
            ->assertSuccessful();
        Http::assertSent(fn (Request $request): bool => (int) $request['message_thread_id'] === 102
            && str_contains($request['text'], '✅ Tes Telegram E-PKL')
            && str_contains($request['text'], 'Waktu: 11 September 2026 17:20:00'));

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->artisan('pkl:telegram-test', ['destination' => 'lain'])
            ->expectsOutput('Tujuan tidak valid. Gunakan: absensi, tkj, tsm, dpb, atau mp.')
            ->assertExitCode(Command::INVALID);
        Http::assertNothingSent();
    }

    private function configureTelegram(): void
    {
        config()->set('services.telegram', [
            'bot_token' => 'test-bot-token',
            'chat_id' => '-1001234567890',
            'threads' => [
                'absensi' => 101,
                'tkj' => 102,
                'tsm' => 103,
                'dpb' => 104,
                'mp' => 105,
            ],
            'connect_timeout' => 1,
            'timeout' => 1,
        ]);
    }

    private function dailyReportForMajor(string $majorCode, ?string $photoPath = null): DailyReport
    {
        $major = Major::factory()->create(['code' => $majorCode]);
        $profile = StudentProfile::factory()
            ->for(User::factory()->approvedStudent()->state(['major_id' => $major->id]))
            ->create(['pkl_place_name' => 'Tempat PKL Murid']);

        return DailyReport::factory()->for($profile)->create([
            'report_date' => today(),
            'activity_description' => 'Menyusun laporan Telegram',
            'activity_photo_path' => $photoPath,
            'activity_photo_original_path' => $photoPath === null ? null : 'daily-report-originals/private-photo.png',
            'had_photo' => $photoPath !== null,
            'photo_delivery_token' => $photoPath === null ? null : 'test-photo-token',
            'created_at' => now(),
        ]);
    }

    private function runAttendanceJob(Attendance $attendance, AttendanceNotificationPhase $phase): void
    {
        (new SendAttendanceTelegramNotification($attendance->id, $phase))->handle(
            app(TelegramClient::class),
            app(TelegramDestinationConfig::class),
            app(TelegramMessageFormatter::class),
        );
    }

    private function runDailyReportJob(DailyReport $report): void
    {
        (new SendDailyReportTelegramNotification($report->id, $report->photo_delivery_token))->handle(
            app(TelegramClient::class),
            app(TelegramDestinationConfig::class),
            app(TelegramMessageFormatter::class),
        );
    }

    private function multipartField(Request $request, string $name): string
    {
        $part = collect($request->data())->firstWhere('name', $name);

        return (string) ($part['contents'] ?? '');
    }

    /** @return array{latitude: float, longitude: float, accuracy: float, selfie: string} */
    private function attendancePayload(): array
    {
        return [
            'latitude' => 1.0,
            'longitude' => 2.0,
            'accuracy' => 9.25,
            'selfie' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        ];
    }
}
