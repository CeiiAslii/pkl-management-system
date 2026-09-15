<?php

namespace App\Exports;

use App\Models\StudentProfile;
use Carbon\CarbonImmutable;
use DOMDocument;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\HeaderFooter;
use OpenSpout\Writer\XLSX\Options\PageMargin;
use OpenSpout\Writer\XLSX\Options\PageOrientation;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\PaperSize;
use OpenSpout\Writer\XLSX\Writer;
use ZipArchive;

class StudentRecapExport
{
    private Options $options;

    public function __construct(
        private StudentProfile $studentProfile,
        private ?CarbonImmutable $dateFrom = null,
        private ?CarbonImmutable $dateTo = null,
    ) {}

    public function generate(): string
    {
        $student = $this->studentProfile->fresh(['user.major']);
        $this->studentProfile = $student;
        $reports = $student->dailyReports()
            ->when($this->dateFrom, fn ($query) => $query->whereDate('report_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('report_date', '<=', $this->dateTo))
            ->orderBy('report_date')->orderBy('id')->get();
        $leaveRequests = $student->leaveRequests()
            ->when($this->dateFrom, fn ($query) => $query->whereDate('requested_for', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('requested_for', '<=', $this->dateTo))
            ->orderBy('requested_for')->orderBy('id')->get();
        $teacherNotes = $student->teacherNotes()
            ->with('teacherProfile.user')
            ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->oldest()->get();
        $attendances = $student->attendances()
            ->when($this->dateFrom, fn ($query) => $query->whereDate('attendance_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('attendance_date', '<=', $this->dateTo))
            ->orderBy('attendance_date')->orderBy('id')->get();

        $temporaryPath = tempnam(sys_get_temp_dir(), 'rekap-pkl-');
        abort_if($temporaryPath === false, 500, 'Gagal menyiapkan berkas rekap.');

        $this->options = new Options(
            DEFAULT_ROW_HEIGHT: 24,
            pageSetup: new PageSetup(PageOrientation::LANDSCAPE, PaperSize::A4, 0, 1),
            pageMargin: new PageMargin(0.5, 0.4, 0.5, 0.4, 0.2, 0.2),
            headerFooter: new HeaderFooter(oddFooter: 'E-PKL | Dicetak: '.now()->format('d/m/Y H:i')),
        );
        $writer = new Writer($this->options);

        try {
            $writer->openToFile($temporaryPath);
            $this->writeSummarySheet(
                $writer,
                $student,
                $reports->count(),
                $leaveRequests->where('type', 'izin')->count(),
                $leaveRequests->where('type', 'sakit')->count(),
                $attendances->count(),
                $teacherNotes->count(),
            );
            $this->writeAttendanceSheet($writer, $attendances);
            $this->writeDailyReportsSheet($writer, $reports);
            $this->writeLeaveRequestsSheet($writer, $leaveRequests);
            $this->writeTeacherNotesSheet($writer, $teacherNotes);
            $writer->close();
            $this->setSummaryPortrait($temporaryPath);
        } catch (\Throwable $exception) {
            @unlink($temporaryPath);

            throw $exception;
        }

        return $temporaryPath;
    }

    public function filename(): string
    {
        $studentName = Str::of($this->studentProfile->user->name)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '-')
            ->trim('-')
            ->limit(70, '')
            ->value();
        $studentName = $studentName !== '' ? $studentName : 'Murid';

        return "Rekap-PKL-{$studentName}.xlsx";
    }

    private function writeSummarySheet(
        Writer $writer,
        StudentProfile $student,
        int $reportCount,
        int $permissionCount,
        int $sickCount,
        int $attendanceCount,
        int $noteCount,
    ): void {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('RINGKASAN');
        $sheet->setColumnWidth(28, 1);
        $sheet->setColumnWidth(62, 2);
        $sheet->setSheetView((new SheetView)->withShowGridLines(false));

        $rows = [
            ['REKAP PKL MURID', ''],
            ['SISTEM MANAJEMEN PRAKTIK KERJA LAPANGAN', ''],
            ['Periode data', $this->periodLabel()],
            ['DATA MURID', ''],
            ['Nama', $student->user->name],
            ['Email', $student->user->email],
            ['Nomor HP', $student->phone ?: '-'],
            ['Jurusan', $student->user->major?->name ?? '-'],
            ['DATA PKL', ''],
            ['Tempat PKL', $student->pkl_place_name ?: '-'],
            ['PIC/Pembimbing', $student->pkl_contact_name ?: '-'],
            ['Nomor Kontak PIC', $student->pkl_contact_phone ?: '-'],
            ['RINGKASAN KEGIATAN', ''],
            ['Total Hari Hadir', $attendanceCount],
            ['Total Izin', $permissionCount],
            ['Total Sakit', $sickCount],
            ['Total Laporan Harian', $reportCount],
            ['Total Catatan Guru', $noteCount],
        ];
        foreach ([1, 2, 4, 9, 13] as $rowNumber) {
            $this->options->mergeCells(0, $rowNumber, 1, $rowNumber);
        }
        foreach ($rows as $index => $row) {
            $style = match ($index) {
                0 => $this->titleStyle(),
                1, 3, 8, 12 => $this->subtitleStyle(),
                default => $this->bodyStyle(),
            };
            $writer->addRow($this->row($row, $style)->withHeight($index === 0 ? 36 : 28));
        }
    }

    private function writeAttendanceSheet(Writer $writer, iterable $attendances): void
    {
        $attendances = collect($attendances);
        $headers = ['No', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Selfie Masuk', 'Selfie Pulang'];
        $sheet = $this->startDataSheet($writer, 'ABSENSI', $headers, $attendances->count());
        $this->setWidths($sheet, [7, 16, 16, 16, 22, 18, 18]);
        $styles = array_fill(0, count($headers), $this->bodyStyle());
        $styles[1] = $this->dateStyle();
        $styles[2] = $this->timeStyle();
        $styles[3] = $this->timeStyle();
        foreach ($attendances as $index => $attendance) {
            $writer->addRow($this->row([
                $index + 1,
                $attendance->attendance_date->toDateTimeImmutable(),
                $attendance->check_in_at->toDateTimeImmutable(),
                $attendance->check_out_at?->toDateTimeImmutable() ?? '-',
                $attendance->check_out_at ? 'Sudah pulang' : 'Sudah masuk',
                $attendance->check_in_selfie_path ? 'Ada' : 'Tidak ada',
                $attendance->check_out_selfie_path ? 'Ada' : 'Tidak ada',
            ], $styles));
        }
    }

    private function writeDailyReportsSheet(Writer $writer, iterable $reports): void
    {
        $reports = collect($reports);
        $headers = ['No', 'Tanggal', 'Kegiatan', 'Foto', 'Waktu Kirim', 'Terakhir Diubah'];
        $sheet = $this->startDataSheet($writer, 'KEGIATAN HARIAN', $headers, $reports->count());
        $this->setWidths($sheet, [7, 15, 70, 14, 21, 21]);
        $bodyStyles = array_fill(0, count($headers), $this->bodyStyle());
        $bodyStyles[1] = $this->dateStyle();
        $bodyStyles[4] = $this->dateTimeStyle();
        $bodyStyles[5] = $this->dateTimeStyle();

        foreach ($reports as $index => $report) {
            $writer->addRow($this->row([
                $index + 1,
                $report->report_date->toDateTimeImmutable(),
                $report->activity_description,
                $report->had_photo ? 'Ada' : 'Tidak ada',
                $report->created_at->toDateTimeImmutable(),
                $report->updated_at->toDateTimeImmutable(),
            ], $bodyStyles));
        }
    }

    private function writeLeaveRequestsSheet(Writer $writer, iterable $leaveRequests): void
    {
        $leaveRequests = collect($leaveRequests);
        $headers = ['No', 'Tanggal', 'Jenis', 'Alasan', 'Status', 'Catatan Guru', 'Bukti', 'Waktu Pengajuan', 'Waktu Review'];
        $sheet = $this->startDataSheet($writer, 'IZIN SAKIT', $headers, $leaveRequests->count());
        $this->setWidths($sheet, [7, 15, 13, 55, 16, 45, 16, 21, 21]);
        $bodyStyles = array_fill(0, count($headers), $this->bodyStyle());
        $bodyStyles[1] = $this->dateStyle();
        $bodyStyles[7] = $this->dateTimeStyle();
        $bodyStyles[8] = $this->dateTimeStyle();

        foreach ($leaveRequests as $index => $leaveRequest) {
            $writer->addRow($this->row([
                $index + 1,
                $leaveRequest->requested_for->toDateTimeImmutable(),
                ucfirst($leaveRequest->type),
                $leaveRequest->reason,
                $this->statusLabel($leaveRequest->status),
                $leaveRequest->teacher_note ?? '-',
                $leaveRequest->supporting_file_path ? 'Ada' : 'Tidak ada',
                $leaveRequest->created_at->toDateTimeImmutable(),
                $leaveRequest->reviewed_at?->toDateTimeImmutable() ?? '-',
            ], $bodyStyles));
        }
    }

    private function writeTeacherNotesSheet(Writer $writer, iterable $teacherNotes): void
    {
        $teacherNotes = collect($teacherNotes);
        $headers = ['No', 'Tanggal', 'Guru', 'Catatan'];
        $sheet = $this->startDataSheet($writer, 'CATATAN GURU', $headers, $teacherNotes->count());
        $this->setWidths($sheet, [7, 15, 32, 75]);
        $bodyStyles = array_fill(0, count($headers), $this->bodyStyle());
        $bodyStyles[1] = $this->dateStyle();

        foreach ($teacherNotes as $index => $teacherNote) {
            $writer->addRow($this->row([
                $index + 1,
                $teacherNote->created_at->toDateTimeImmutable(),
                $teacherNote->teacherProfile->user->name,
                $teacherNote->content,
            ], $bodyStyles));
        }
    }

    /** @param list<string> $headers */
    private function startDataSheet(Writer $writer, string $name, array $headers, int $recordCount): Sheet
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($name);
        $sheet->setSheetView((new SheetView)->withFreezeRow(9)->withShowGridLines(false));
        $sheet->setPrintTitleRows('8:8');
        $sheet->setAutoFilter(new AutoFilter(0, 8, count($headers) - 1, 8 + $recordCount));
        $student = $this->studentProfile;
        $headingRows = [
            "REKAP PKL - {$name}", 'SISTEM MANAJEMEN PRAKTIK KERJA LAPANGAN',
            'Nama: '.$student->user->name,
            'Jurusan: '.($student->user->major?->name ?? '-'),
            'Tempat PKL: '.($student->pkl_place_name ?: '-'),
            'Periode: '.$this->periodLabel(),
        ];
        foreach ($headingRows as $index => $text) {
            $this->options->mergeCells(0, $index + 1, count($headers) - 1, $index + 1, $sheet->getIndex());
            $writer->addRow($this->row(array_pad([$text], count($headers), ''), match ($index) {
                0 => $this->titleStyle(), 1 => $this->subtitleStyle(), default => $this->bodyStyle(),
            })->withHeight($index === 0 ? 36 : 24));
        }
        $writer->addRow(Row::fromValues([''])->withHeight(10));
        $writer->addRow($this->row($headers, $this->headerStyle())->withHeight(32));
        if ($recordCount === 0) {
            $empty = match ($name) {
                'ABSENSI' => 'Belum ada data absensi.',
                'KEGIATAN HARIAN' => 'Belum ada laporan harian.',
                'IZIN SAKIT' => 'Belum ada pengajuan izin/sakit.',
                default => 'Belum ada catatan guru.',
            };
            $this->options->mergeCells(0, 9, count($headers) - 1, 9, $sheet->getIndex());
            $writer->addRow($this->row([$empty], $this->bodyStyle())->withHeight(30));
        }

        return $sheet;
    }

    /** @param list<int|float> $widths */
    private function setWidths(Sheet $sheet, array $widths): void
    {
        foreach ($widths as $index => $width) {
            $sheet->setColumnWidth($width, $index + 1);
        }
    }

    private function titleStyle(): Style
    {
        return new Style(
            fontBold: true,
            fontSize: 16,
            fontColor: Color::WHITE,
            cellVerticalAlignment: CellVerticalAlignment::CENTER,
            backgroundColor: '16324F',
        );
    }

    private function subtitleStyle(): Style
    {
        return new Style(
            fontBold: true,
            fontColor: Color::DARK_BLUE,
            backgroundColor: 'DDEBF7',
        );
    }

    private function headerStyle(): Style
    {
        return new Style(
            fontBold: true,
            fontColor: Color::WHITE,
            cellAlignment: CellAlignment::CENTER,
            cellVerticalAlignment: CellVerticalAlignment::CENTER,
            shouldWrapText: true,
            backgroundColor: '235C87',
        );
    }

    private function bodyStyle(): Style
    {
        return new Style(
            fontSize: 11,
            border: new Border(...array_map(fn (BorderName $name): BorderPart => new BorderPart($name, 'D5E0EA'), BorderName::cases())),
            cellVerticalAlignment: CellVerticalAlignment::TOP,
            shouldWrapText: true,
        );
    }

    /** @param list<mixed> $values
     * @param  Style|list<Style>  $styles
     */
    private function row(array $values, Style|array $styles): Row
    {
        $cells = [];
        foreach ($values as $index => $value) {
            $style = is_array($styles) ? $styles[$index] : $styles;
            $cells[] = is_string($value) ? new StringCell($value, $style) : Cell::fromValue($value, $style);
        }

        return new Row($cells);
    }

    private function dateStyle(): Style
    {
        return $this->bodyStyle()->withFormat('dd/mm/yyyy');
    }

    private function dateTimeStyle(): Style
    {
        return $this->bodyStyle()->withFormat('dd/mm/yyyy hh:mm');
    }

    private function timeStyle(): Style
    {
        return $this->bodyStyle()->withFormat('hh:mm');
    }

    private function setSummaryPortrait(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Gagal mengatur halaman rekap.');
        }
        try {
            $xml = new DOMDocument;
            $xml->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
            $xml->getElementsByTagName('pageSetup')->item(0)->setAttribute('orientation', 'portrait');
            if (! $zip->addFromString('xl/worksheets/sheet1.xml', $xml->saveXML())) {
                throw new \RuntimeException('Gagal menyimpan pengaturan halaman rekap.');
            }
        } finally {
            $zip->close();
        }
    }

    private function periodLabel(): string
    {
        return match (true) {
            $this->dateFrom !== null && $this->dateTo !== null => $this->dateFrom->format('d/m/Y').' s.d. '.$this->dateTo->format('d/m/Y'),
            $this->dateFrom !== null => 'Mulai '.$this->dateFrom->format('d/m/Y'),
            $this->dateTo !== null => 'Sampai '.$this->dateTo->format('d/m/Y'),
            default => 'Semua riwayat',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Menunggu',
        };
    }
}
