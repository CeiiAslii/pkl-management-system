<?php

namespace Tests\Feature;

use App\Actions\StoreDailyReportPhoto;
use App\Jobs\SendDailyReportTelegramNotification;
use App\Models\DailyReport;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DailyReportImageValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_normal_2400_by_1800_photo_passes_validation_and_reaches_storage_action(): void
    {
        Queue::fake([SendDailyReportTelegramNotification::class]);
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->mock(StoreDailyReportPhoto::class)
            ->shouldReceive('handle')
            ->once()
            ->andReturn([
                'web_path' => 'daily-report-photos/test.jpg',
                'original_path' => 'daily-report-originals/test.jpg',
                'delivery_token' => 'test-delivery-token',
            ]);

        $this->actingAs($profile->user)->post(route('student.daily-reports.store'), [
            'report_date' => today()->toDateString(),
            'activity_description' => 'Foto normal diterima.',
            'activity_photo' => $this->pngWithDimensions(2400, 1800),
        ])->assertRedirectToRoute('student.daily-reports.index');

        $this->assertDatabaseHas('daily_reports', [
            'student_profile_id' => $profile->id,
            'activity_description' => 'Foto normal diterima.',
        ]);
        Queue::assertPushed(SendDailyReportTelegramNotification::class, 1);
    }

    #[TestWith([4097, 100])]
    #[TestWith([100, 4097])]
    #[TestWith([5000, 10])]
    public function test_oversized_raster_is_rejected_before_photo_storage(int $width, int $height): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->mock(StoreDailyReportPhoto::class)->shouldNotReceive('handle');

        $this->actingAs($profile->user)->postJson(route('student.daily-reports.store'), [
            'report_date' => today()->toDateString(),
            'activity_description' => 'Foto terlalu besar.',
            'activity_photo' => $this->pngWithDimensions($width, $height),
        ])->assertUnprocessable()->assertJsonValidationErrors('activity_photo');

        $this->assertDatabaseCount('daily_reports', 0);
    }

    #[TestWith([4097, 100])]
    #[TestWith([100, 4097])]
    public function test_replacement_photo_uses_the_same_dimension_gate_before_storage(int $width, int $height): void
    {
        $profile = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $report = DailyReport::factory()->for($profile)->create([
            'activity_description' => 'Laporan asli.',
        ]);
        $this->mock(StoreDailyReportPhoto::class)->shouldNotReceive('handle');

        $this->actingAs($profile->user)->patchJson(route('student.daily-reports.update', $report), [
            'report_date' => $report->report_date->toDateString(),
            'activity_description' => 'Tidak boleh tersimpan.',
            'activity_photo' => $this->pngWithDimensions($width, $height),
        ])->assertUnprocessable()->assertJsonValidationErrors('activity_photo');

        $this->assertSame('Laporan asli.', $report->fresh()->activity_description);
    }

    private function pngWithDimensions(int $width, int $height): UploadedFile
    {
        $chunk = static function (string $type, string $data): string {
            return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        };
        $header = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

        return UploadedFile::fake()->createWithContent(
            'dimensions.png',
            "\x89PNG\r\n\x1a\n".$chunk('IHDR', $header).$chunk('IEND', ''),
        );
    }
}
