<x-student-layout title="Dashboard">
    <header class="mb-4">
        <p class="text-sm font-semibold text-sky-700">{{ today()->translatedFormat('l, d F Y') }}</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Selamat datang, {{ auth()->user()->name }}</h1>
    </header>

    @if (! $studentProfile?->isProfileComplete())
        <section class="mb-4 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-sm font-bold text-amber-900">Profil belum lengkap</h2><p class="mt-1 text-sm text-amber-800">Lengkapi data tempat PKL agar informasi PKL Anda lengkap.</p></div>
            <a href="{{ route('student.profile.show') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-white px-4 text-sm font-bold text-sky-700">Lengkapi Profil</a>
        </section>
    @endif

    <div class="grid gap-4 lg:grid-cols-[1.15fr_.85fr]">
        <section class="rounded-2xl bg-sky-700 p-4 text-white shadow-sm sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-sm font-semibold text-sky-100">Status hari ini</p><h2 class="mt-1 text-xl font-bold">{{ ! $todayAttendance ? 'Belum absen' : ($todayAttendance->check_out_at ? 'Sudah pulang' : 'Sudah masuk') }}</h2></div>
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">{{ ! $todayAttendance ? 'Belum mulai' : ($todayAttendance->check_out_at ? 'Selesai' : 'Berlangsung') }}</span>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white/10 p-3"><dt class="text-xs font-semibold text-sky-100">Jam masuk</dt><dd class="mt-1 text-lg font-bold">{{ $todayAttendance?->check_in_at?->format('H:i') ?? '—' }}</dd></div>
                <div class="rounded-xl bg-white/10 p-3"><dt class="text-xs font-semibold text-sky-100">Jam pulang</dt><dd class="mt-1 text-lg font-bold">{{ $todayAttendance?->check_out_at?->format('H:i') ?? '—' }}</dd></div>
            </dl>
            <div class="mt-4 grid grid-cols-2 gap-2">
                @if (! $todayAttendance)
                    <a href="{{ route('student.attendance') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-white px-3 text-center text-sm font-bold text-sky-800">Absen Masuk</a>
                @else
                    <span class="flex min-h-12 items-center justify-center rounded-xl bg-white/15 px-3 text-center text-sm font-bold text-sky-100">Masuk selesai</span>
                @endif
                @if ($todayAttendance && ! $todayAttendance->check_out_at)
                    <a href="{{ route('student.attendance') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-white px-3 text-center text-sm font-bold text-sky-800">Absen Pulang</a>
                @else
                    <span class="flex min-h-12 items-center justify-center rounded-xl bg-white/15 px-3 text-center text-sm font-bold text-sky-100">{{ $todayAttendance?->check_out_at ? 'Pulang selesai' : 'Absen Pulang' }}</span>
                @endif
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3">
            <a href="{{ $hasReportToday ? route('student.daily-reports.index') : route('student.daily-reports.create') }}" class="flex min-h-32 flex-col justify-between rounded-2xl border border-sky-200 bg-white p-4 shadow-sm transition hover:bg-sky-50">
                <span class="text-sm font-semibold text-sky-600">Laporan</span>
                <strong class="text-base text-slate-900">{{ $hasReportToday ? 'Laporan Hari Ini Selesai' : 'Isi Laporan Harian' }}</strong>
            </a>
            <a href="{{ route('student.leave-requests.create') }}" class="flex min-h-32 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:bg-slate-50">
                <span class="text-sm font-semibold text-slate-500">Ketidakhadiran</span>
                <strong class="text-base text-slate-900">Ajukan Izin / Sakit</strong>
            </a>
        </section>
    </div>

    <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="font-bold text-slate-900">Catatan Guru</h2>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse ($teacherNotes as $note)
                <article class="py-3 first:pt-0 last:pb-0"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-semibold text-sky-800">{{ $note->teacherProfile->user?->name ?? 'Guru' }}</h3><time class="text-xs text-slate-500">{{ $note->created_at->translatedFormat('d M Y H:i') }}</time></div><p class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $note->content }}</p></article>
            @empty
                <p class="text-sm text-slate-500">Belum ada catatan guru.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-4">
        <h2 class="mb-3 font-bold text-slate-900">Informasi terbaru</h2>
        <div class="grid gap-3 md:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">Laporan harian</p>
                @if ($dailyReports->first())<p class="mt-2 text-sm font-semibold text-slate-900">{{ $dailyReports->first()->report_date->translatedFormat('d M Y') }}</p><p class="mt-1 text-sm leading-5 text-slate-600">{{ str($dailyReports->first()->activity_description)->limit(90) }}</p>@else<p class="mt-2 text-sm text-slate-500">Belum ada laporan harian.</p>@endif
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">Izin / sakit</p>
                @if ($leaveRequests->first())<p class="mt-2 text-sm font-semibold text-slate-900">{{ ucfirst($leaveRequests->first()->type) }} · {{ $leaveRequests->first()->requested_for->translatedFormat('d M Y') }}</p><p class="mt-1 text-sm text-slate-600">{{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$leaveRequests->first()->status] }}</p>@else<p class="mt-2 text-sm text-slate-500">Belum ada pengajuan.</p>@endif
            </article>

        </div>
    </section>
</x-student-layout>
