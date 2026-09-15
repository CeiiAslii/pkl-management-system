<?php

namespace App\Services;

use App\Enums\AttendanceNotificationPhase;
use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\StudentProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class TelegramMessageFormatter
{
    public function attendance(Attendance $attendance, AttendanceNotificationPhase $phase): string
    {
        $profile = $attendance->studentProfile;
        $isCheckIn = $phase === AttendanceNotificationPhase::CheckIn;
        $lines = [
            $isCheckIn ? '✅ ABSEN MASUK' : '🏁 ABSEN PULANG',
            '',
            "Nama: {$profile->user->name}",
            'Jurusan: '.$this->majorCode($profile),
            'Tempat PKL: '.($profile->pkl_place_name ?: 'Belum diisi'),
            'Tanggal: '.$attendance->attendance_date->locale('id')->translatedFormat('d F Y'),
            'Jam Masuk: '.$attendance->check_in_at->format('H:i'),
        ];

        if (! $isCheckIn) {
            $lines[] = 'Jam Pulang: '.($attendance->check_out_at?->format('H:i') ?? '-');
        }

        array_push(
            $lines,
            '',
            $isCheckIn ? '📍 Lokasi' : '📍 Lokasi Pulang',
            'Latitude: '.($isCheckIn ? $attendance->check_in_latitude : $attendance->check_out_latitude),
            'Longitude: '.($isCheckIn ? $attendance->check_in_longitude : $attendance->check_out_longitude),
            'Akurasi: ±'.$this->accuracy($isCheckIn ? $attendance->check_in_accuracy : $attendance->check_out_accuracy).' m',
        );

        return implode("\n", $lines);
    }

    public function dailyReport(DailyReport $dailyReport): string
    {
        $profile = $dailyReport->studentProfile;

        return implode("\n", [
            '📝 LAPORAN KEGIATAN PKL',
            '',
            "Nama: {$profile->user->name}",
            'Jurusan: '.$this->majorCode($profile),
            'Tempat PKL: '.($profile->pkl_place_name ?: 'Belum diisi'),
            'Tanggal: '.$dailyReport->report_date->locale('id')->translatedFormat('d F Y'),
            '',
            'Kegiatan:',
            $dailyReport->activity_description,
            '',
            'Waktu Kirim: '.$dailyReport->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ]);
    }

    /** @param array{report_date?: array{before: string, after: string}, activity_description?: array{before: string, after: string}, photo?: string} $changes */
    public function dailyReportUpdated(DailyReport $report, array $changes): string
    {
        $profile = $report->studentProfile;
        $lines = [
            '✏️ LAPORAN PKL DIPERBARUI', '',
            'Nama: '.$profile->user->name,
            'Jurusan: '.$this->majorCode($profile),
            'Tempat PKL: '.($profile->pkl_place_name ?: 'Belum diisi'),
            'Tanggal Kegiatan: '.$report->report_date->locale('id')->translatedFormat('d F Y'),
            '', 'Perubahan:',
        ];
        if (isset($changes['report_date'])) {
            $lines[] = 'Tanggal: '.CarbonImmutable::parse($changes['report_date']['before'])->format('d/m/Y').' → '.CarbonImmutable::parse($changes['report_date']['after'])->format('d/m/Y');
        }
        if (isset($changes['activity_description'])) {
            array_push($lines, '', 'Kegiatan:', 'Sebelumnya:', Str::limit($changes['activity_description']['before'], 900), 'Menjadi:', Str::limit($changes['activity_description']['after'], 900));
        }
        if (isset($changes['photo'])) {
            array_push($lines, '', 'Foto:', $changes['photo']);
        }
        array_push($lines, '', 'Waktu Diperbarui: '.$report->updated_at->locale('id')->translatedFormat('d F Y H:i'));

        return implode("\n", $lines);
    }

    public function majorCode(StudentProfile $profile): string
    {
        return $profile->user->major?->code ?? '-';
    }

    private function accuracy(int|float|string|null $accuracy): string
    {
        return rtrim(rtrim(number_format((float) $accuracy, 2, '.', ''), '0'), '.');
    }
}
