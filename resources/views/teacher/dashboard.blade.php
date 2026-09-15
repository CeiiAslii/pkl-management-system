<x-teacher-layout title="Dashboard">
    <section class="mb-3 flex flex-col justify-between gap-1.5 rounded-2xl bg-sky-700 p-3.5 text-white shadow-sm sm:flex-row sm:items-center sm:gap-4 sm:p-5">
        <div><p class="text-xs font-medium text-sky-100 sm:text-sm">Selamat datang,</p><h2 class="text-lg font-bold leading-6 sm:mt-1 sm:text-2xl">{{ auth()->user()->name }}</h2></div>
        <p class="max-w-sm text-xs leading-5 text-sky-100 sm:text-sm">Pantau kegiatan PKL seluruh murid aktif hari ini.</p>
    </section>

    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 xl:grid-cols-5">
        <x-teacher.summary-card label="Total murid" :value="$totalStudents" />
        <x-teacher.summary-card label="Laporan hari ini" :value="$reportsToday" />
        <x-teacher.summary-card label="Izin/sakit menunggu" :value="$pendingPermission + $pendingSick" tone="amber" />
        <x-teacher.summary-card label="Sudah absen" :value="$attendedToday" tone="emerald" />
        <x-teacher.summary-card label="Belum absen" :value="max(0, $totalStudents - $attendedToday)" tone="slate" class="col-span-2 sm:col-span-1" />
    </div>

    <div class="mt-3 grid gap-3 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3"><h3 class="font-bold text-slate-900">Laporan terbaru</h3><a href="{{ route('teacher.daily-reports.index') }}" class="text-sm font-semibold text-sky-700">Lihat semua</a></div>
            @forelse ($recentReports->take(3) as $report)
                <a href="{{ route('teacher.daily-reports.show', $report) }}" class="block border-t border-slate-100 py-2.5 first:border-0">
                    <p class="font-semibold text-slate-800">{{ $report->studentProfile->user->name }}</p>
                    <p class="truncate text-sm text-slate-500">{{ $report->activity_description }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $report->report_date->format('d M Y') }}</p>
                </a>
            @empty
                <x-teacher.empty-state message="Belum ada laporan murid." class="py-4" />
            @endforelse
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3"><h3 class="font-bold text-slate-900">Izin / sakit terbaru</h3><a href="{{ route('teacher.leave-requests.index') }}" class="text-sm font-semibold text-sky-700">Lihat semua</a></div>
            @forelse ($pendingRequests->take(3) as $leave)
                <div class="border-t border-slate-100 py-2.5 first:border-0">
                    <div class="flex items-center justify-between gap-2"><p class="font-semibold text-slate-800">{{ $leave->studentProfile->user->name }}</p><span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700">Menunggu</span></div>
                    <p class="mt-1 text-sm text-slate-500">{{ ucfirst($leave->type) }} · {{ $leave->requested_for->format('d M Y') }}</p>
                </div>
            @empty
                <x-teacher.empty-state message="Tidak ada permohonan yang menunggu." class="py-4" />
            @endforelse
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 font-bold text-slate-900">Murid terbaru</h3>
            @forelse ($recentStudents as $student)
                <div class="border-t border-slate-100 py-2.5 first:border-0">
                    <p class="font-semibold text-slate-800">{{ $student->user->name }}</p>
                    <p class="text-sm text-slate-500">{{ $student->user->major?->code ?? 'Jurusan belum diisi' }} · {{ $student->pkl_place_name ?? 'Tempat PKL belum diisi' }}</p><x-pkl-map-link :profile="$student" />
                </div>
            @empty
                <x-teacher.empty-state message="Belum ada murid aktif." class="py-4" />
            @endforelse
        </section>
    </div>
</x-teacher-layout>
