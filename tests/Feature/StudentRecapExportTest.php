<?php

namespace Tests\Feature;

use App\Exports\StudentRecapExport;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\TeacherNote;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DOMDocument;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;
use ZipArchive;

class StudentRecapExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_teacher_downloads_a_complete_dynamic_workbook_without_private_paths(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $student = StudentProfile::factory()
            ->for(User::factory()->approvedStudent()->state(['name' => 'Murid Demo B', 'email' => 'murid.demo.b@example.test']))
            ->create(['pkl_place_name' => 'CV Rekap PKL', 'pkl_latitude' => 1.0, 'pkl_longitude' => 2.0, 'pkl_location_accuracy' => 12.5]);
        Attendance::factory()->checkedOut()->for($student)->create([
            'attendance_date' => '2026-09-08',
            'check_in_selfie_path' => 'attendance-selfies/private-check-in.jpg',
            'check_out_selfie_path' => 'attendance-selfies/private-check-out.jpg',
            'late_status' => 'late',
        ]);
        DailyReport::factory()->for($student)->create([
            'report_date' => '2026-09-08',
            'activity_description' => 'Memeriksa perangkat jaringan.',
            'activity_photo_path' => 'daily-report-photos/private-report.jpg',
            'had_photo' => true,
        ]);
        LeaveRequest::factory()->for($student)->create([
            'requested_for' => '2026-09-09',
            'type' => 'izin',
            'reason' => 'Mengurus dokumen sekolah.',
            'supporting_file_path' => 'leave-request-files/private-proof.pdf',
        ]);
        TeacherNote::factory()->for($teacher)->for($student)->create(['content' => 'Perkembangan PKL baik.']);

        $this->actingAs($teacher->user)->get(route('student-recaps.export', $student))
            ->assertOk()
            ->assertDownload('Rekap-PKL-Murid-Demo-B.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $workbook = $this->readWorkbook((new StudentRecapExport($student))->generate());

        $this->assertSame(
            ['RINGKASAN', 'ABSENSI', 'KEGIATAN HARIAN', 'IZIN SAKIT', 'CATATAN GURU'],
            array_keys($workbook),
        );
        $contents = json_encode($workbook, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Murid Demo B', $contents);
        $this->assertStringContainsString('CV Rekap PKL', $contents);
        $this->assertStringNotContainsString('query=', $contents);
        $this->assertStringNotContainsString('Kelas', $contents);
        $this->assertStringContainsString('Memeriksa perangkat jaringan.', $contents);
        $this->assertStringContainsString('Mengurus dokumen sekolah.', $contents);
        $this->assertStringContainsString('Perkembangan PKL baik.', $contents);
        $this->assertStringNotContainsString('1.0000000', $contents);
        $this->assertStringNotContainsString('2.0000000', $contents);
        $this->assertStringNotContainsString('private-check-in.jpg', $contents);
        $this->assertStringNotContainsString('private-check-out.jpg', $contents);
        $this->assertStringNotContainsString('private-report.jpg', $contents);
        $this->assertStringNotContainsString('private-proof.pdf', $contents);
    }

    public function test_optional_date_range_filters_history_while_no_range_includes_all_history(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        DailyReport::factory()->for($student)->create([
            'report_date' => '2026-08-01',
            'activity_description' => 'Laporan lama tetap tersimpan',
        ]);
        DailyReport::factory()->for($student)->create([
            'report_date' => '2026-09-10',
            'activity_description' => 'Laporan dalam rentang',
        ]);

        $allHistory = json_encode($this->readWorkbook((new StudentRecapExport($student))->generate()), JSON_THROW_ON_ERROR);
        $rangedHistory = json_encode($this->readWorkbook((new StudentRecapExport(
            $student,
            CarbonImmutable::parse('2026-09-01'),
            CarbonImmutable::parse('2026-09-30'),
        ))->generate()), JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('Laporan lama tetap tersimpan', $allHistory);
        $this->assertStringContainsString('Laporan dalam rentang', $allHistory);
        $this->assertStringNotContainsString('Laporan lama tetap tersimpan', $rangedHistory);
        $this->assertStringContainsString('Laporan dalam rentang', $rangedHistory);
        $this->assertDatabaseCount('daily_reports', 2);
    }

    public function test_photo_history_in_excel_does_not_depend_on_physical_files(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        DailyReport::factory()->for($student)->create([
            'report_date' => '2026-09-12',
            'activity_description' => 'Pernah memiliki foto',
            'had_photo' => true,
            'activity_photo_path' => null,
            'activity_photo_original_path' => null,
        ]);
        DailyReport::factory()->for($student)->create([
            'report_date' => '2026-09-13',
            'activity_description' => 'Tidak pernah memiliki foto',
            'had_photo' => false,
        ]);

        $rows = $this->readWorkbook((new StudentRecapExport($student))->generate())['KEGIATAN HARIAN'];
        $contents = json_encode($rows, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('Ada', $contents);
        $this->assertStringContainsString('Tidak ada', $contents);
        $this->assertStringNotContainsString('daily-report-', $contents);
        $this->assertStringNotContainsString('latitude', strtolower($contents));
        $this->assertStringNotContainsString('longitude', strtolower($contents));
        $this->assertStringNotContainsString('GPS', $contents);
    }

    public function test_admin_can_download_but_students_and_guests_cannot_export_recaps(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['name' => 'Murid Ekspor']))->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('student-recaps.export', $student))
            ->assertOk()
            ->assertDownload('Rekap-PKL-Murid-Ekspor.xlsx');

        $otherStudent = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $this->actingAs($otherStudent->user)->get(route('student-recaps.export', $student))->assertForbidden();
        auth()->logout();
        $this->get(route('student-recaps.export', $student))->assertRedirectToRoute('login');
    }

    public function test_teacher_cannot_export_an_inactive_or_deleted_student(): void
    {
        $teacher = TeacherProfile::factory()->create();
        $pending = StudentProfile::factory()->create();
        $deleted = StudentProfile::factory()->for(User::factory()->approvedStudent())->create();
        $deleted->user->delete();

        $this->actingAs($teacher->user)->get(route('student-recaps.export', $pending))->assertNotFound();
        $this->get(route('student-recaps.export', $deleted))->assertNotFound();
    }

    public function test_official_workbook_is_current_private_and_formatted_for_print(): void
    {
        Queue::fake();
        $this->travelTo(CarbonImmutable::parse('2026-09-12 13:20:00', config('app.timezone')));
        $major = Major::factory()->create(['code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan']);
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent()->for($major)->state(['name' => 'Murid Contoh', 'email' => 'contoh@example.test']))->create([
            'phone' => '080000000000', 'pkl_place_name' => 'CV Jaringan Mandiri',
            'pkl_contact_name' => 'PIC Demo', 'pkl_contact_phone' => '080000000001',
            'pkl_latitude' => 1.1234567, 'pkl_longitude' => 2.7654321, 'pkl_location_accuracy' => 4321.98,
        ]);
        $teacher = TeacherProfile::factory()->for(User::factory()->teacher()->state(['name' => 'Guru Demo']))->create();
        Attendance::factory()->checkedOut()->for($student)->create([
            'attendance_date' => '2026-09-11', 'check_in_at' => '2026-09-11 08:00:00', 'check_out_at' => '2026-09-11 16:00:00',
            'check_in_latitude' => 1.9876543, 'check_in_longitude' => 2.8765432,
            'check_out_latitude' => 1.2345678, 'check_out_longitude' => 2.6543210,
            'check_in_accuracy' => 9876.54, 'check_out_accuracy' => 8765.43,
        ]);
        $report = DailyReport::factory()->for($student)->create([
            'report_date' => '2026-09-11', 'activity_description' => 'Isi sebelum revisi',
            'created_at' => '2026-09-11 16:05:00', 'activity_photo_path' => 'daily-report-photos/private.png',
            'had_photo' => true,
        ]);
        LeaveRequest::factory()->for($student)->create([
            'requested_for' => '2026-09-10', 'type' => 'izin', 'reason' => 'Mengurus dokumen sekolah.',
            'status' => 'approved', 'teacher_note' => 'Silakan, tetap kabari pembimbing.',
            'supporting_file_path' => 'leave-request-files/private.pdf', 'reviewed_at' => '2026-09-10 07:30:00',
        ]);
        TeacherNote::factory()->for($student)->for($teacher)->create(['content' => 'Dokumentasi kegiatan sudah bagus.', 'created_at' => '2026-09-11 17:00:00']);
        $export = new StudentRecapExport($student);
        $before = $this->readWorkbook($export->generate());
        $this->assertStringContainsString('Isi sebelum revisi', json_encode($before, JSON_THROW_ON_ERROR));
        $this->actingAs($student->user)->patch(route('student.daily-reports.update', $report), [
            'report_date' => '2026-09-11',
            'activity_description' => 'Melakukan instalasi kabel LAN, konfigurasi router, dan pengecekan koneksi. Mendokumentasikan hasil pengujian serta merapikan perangkat setelah pekerjaan selesai.',
        ])->assertSessionHasNoErrors();
        $path = $export->generate();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        for ($index = 1; $index <= 5; $index++) {
            $xml = new DOMDocument;
            $this->assertTrue($xml->loadXML($zip->getFromName("xl/worksheets/sheet{$index}.xml")));
            $setup = $xml->getElementsByTagName('pageSetup')->item(0);
            $this->assertSame($index === 1 ? 'portrait' : 'landscape', $setup->getAttribute('orientation'));
            $this->assertSame('1', $setup->getAttribute('fitToWidth'));
            $this->assertStringContainsString('Dicetak: 12/09/2026 13:20', $xml->textContent);
            if ($index > 1) {
                $this->assertSame('8', $xml->getElementsByTagName('pane')->item(0)->getAttribute('ySplit'));
                $this->assertStringStartsWith('A8:', $xml->getElementsByTagName('autoFilter')->item(0)->getAttribute('ref'));
            }
            $this->assertSame(0, $xml->getElementsByTagName('f')->length);
        }
        $zip->close();
        if ($samplePath = getenv('PKL_RECAP_SAMPLE_PATH')) {
            copy($path, $samplePath);
        }
        $workbook = $this->readWorkbook($path);
        $contents = json_encode($workbook, JSON_THROW_ON_ERROR);
        foreach (['Murid Contoh', 'contoh@example.test', '080000000000', 'Teknik Komputer dan Jaringan', 'CV Jaringan Mandiri', 'PIC Demo', '080000000001', 'Melakukan instalasi kabel LAN', '2026-09-12 13:20:00', 'Guru Demo'] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
        foreach (['Isi sebelum revisi', '1.1234567', '2.7654321', '4321.98', '1.9876543', '2.8765432', '1.2345678', '2.6543210', '9876.54', '8765.43', 'google.com', 'daily-report-photos/', 'leave-request-files/', 'NIS', 'Kelas', 'Tahun Ajaran', 'Akurasi', 'Lokasi Masuk', 'Latitude', 'Longitude', 'Total Terlambat'] as $privateOrObsolete) {
            $this->assertStringNotContainsString($privateOrObsolete, $contents);
        }
        $summary = array_column($workbook['RINGKASAN'], 1, 0);
        $this->assertSame(1, $summary['Total Hari Hadir']);
        $this->assertSame(1, $summary['Total Laporan Harian']);
        $this->assertSame(1, $summary['Total Catatan Guru']);
        $this->assertSame(1, $summary['Total Izin']);
        $this->assertSame(0, $summary['Total Sakit']);
    }

    public function test_empty_workbook_has_intentional_empty_states_and_optional_placeholders(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent())->create([
            'pkl_place_name' => null, 'pkl_contact_name' => null, 'pkl_contact_phone' => null,
        ]);
        $workbook = $this->readWorkbook((new StudentRecapExport($student))->generate());
        $this->assertCount(5, $workbook);
        foreach (['ABSENSI' => 'Belum ada data absensi.', 'KEGIATAN HARIAN' => 'Belum ada laporan harian.', 'IZIN SAKIT' => 'Belum ada pengajuan izin/sakit.', 'CATATAN GURU' => 'Belum ada catatan guru.'] as $sheet => $message) {
            $this->assertSame($message, end($workbook[$sheet])[0]);
        }
        $summary = array_column($workbook['RINGKASAN'], 1, 0);
        $this->assertSame('-', $summary['PIC/Pembimbing']);
        $this->assertSame('-', $summary['Nomor Kontak PIC']);
        $this->assertSame(0, $summary['Total Laporan Harian']);
    }

    public function test_user_content_is_exported_as_text_instead_of_executable_formulas(): void
    {
        $student = StudentProfile::factory()->for(User::factory()->approvedStudent()->state(['name' => '../Nama / Murid']))->create();
        DailyReport::factory()->for($student)->create(['activity_description' => '=HYPERLINK("https://example.test", "Unsafe")']);
        $export = new StudentRecapExport($student);
        $this->assertSame('Rekap-PKL-Nama-Murid.xlsx', $export->filename());
        $path = $export->generate();
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = new DOMDocument;
        $xml->loadXML($zip->getFromName('xl/worksheets/sheet3.xml'));
        $this->assertSame(0, $xml->getElementsByTagName('f')->length);
        $this->assertStringContainsString('=HYPERLINK', $xml->textContent);
        $zip->close();
        unlink($path);
    }

    /**
     * @return array<string, list<list<mixed>>>
     */
    private function readWorkbook(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $workbook = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(
                    fn (mixed $value): mixed => $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value,
                    $row->toArray(),
                );
            }
            $workbook[$sheet->getName()] = $rows;
        }

        $reader->close();
        unlink($path);

        return $workbook;
    }
}
