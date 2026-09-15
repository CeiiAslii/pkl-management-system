<x-admin-layout title="Dashboard">
    <header class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm font-semibold text-sky-700">{{ today()->translatedFormat('l, d F Y') }}</p><h2 class="mt-1 text-2xl font-bold text-slate-900">Ringkasan PKL sekolah</h2><p class="mt-1 text-sm text-slate-500">Pantau pendaftaran dan kegiatan murid dalam satu tempat.</p></div><a href="{{ route('admin.registrations.index') }}" class="inline-flex min-h-11 items-center rounded-xl bg-sky-700 px-4 text-sm font-bold text-white">Kelola Pendaftaran</a></header>
    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Ringkasan operasional">
        @foreach ([['Murid Aktif', $activeStudents], ['Guru', $totalTeachers], ['Pendaftaran Pending', $pendingStudents], ['Sudah Absen Hari Ini', $attendedToday], ['Belum Absen Hari Ini', $notAttendedToday], ['Laporan Hari Ini', $reportsToday], ['Izin/Sakit Menunggu', $pendingLeaves]] as [$label, $count])
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold leading-5 text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">{{ $count }}</p></article>
        @endforeach
        <article class="rounded-2xl bg-sky-800 p-4 text-white"><p class="text-xs font-semibold text-sky-100">Aktivitas PKL Hari Ini</p><p class="mt-2 text-sm leading-6">{{ $attendedToday }} murid sudah masuk · {{ $reportsToday }} laporan kegiatan</p><p class="mt-1 text-xs text-sky-100">{{ $pendingLeaves }} izin/sakit menunggu review guru.</p></article>
    </section>
    <nav class="my-5 flex flex-wrap gap-2" aria-label="Aksi cepat">
        @foreach ([['Data Murid', 'admin.students.index'], ['Tambah Guru', 'admin.teachers.create'], ['Kelola Jurusan', 'admin.majors.index']] as [$label, $route])
            <a href="{{ route($route) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-sky-700 hover:bg-sky-50">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="grid gap-4 xl:grid-cols-2">
        <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><header class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-900">Pendaftaran Terbaru</h3><a href="{{ route('admin.registrations.index') }}" class="text-sm font-semibold text-sky-700">Kelola</a></header><div class="divide-y divide-slate-100">
            @forelse ($latestRegistrations as $student)
                <a href="{{ route('admin.students.show', $student) }}" class="flex items-start justify-between gap-3 p-4 hover:bg-slate-50"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $student->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $student->major?->code ?? 'Jurusan belum diisi' }} · {{ $student->created_at->format('d M H:i') }}</p></div><span class="shrink-0 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800">{{ ['pending' => 'Menunggu', 'active' => 'Aktif', 'suspended' => 'Ditangguhkan'][$student->status->value] }}</span></a>
            @empty<p class="p-5 text-sm text-slate-500">Belum ada pendaftaran murid.</p>@endforelse
        </div></section>
        <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><header class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-900">Laporan Hari Ini</h3></header><div class="divide-y divide-slate-100">
            @forelse ($latestReports as $report)
                <article class="p-4"><div class="flex flex-wrap items-center justify-between gap-2"><a href="{{ route('admin.students.show', $report->studentProfile->user) }}" class="text-sm font-semibold text-sky-800">{{ $report->studentProfile->user->name }}</a><span class="text-xs text-slate-500">{{ $report->studentProfile->user->major?->code ?? '—' }} · {{ $report->created_at->format('d M H:i') }}</span></div><p class="mt-2 break-words text-sm leading-6 text-slate-600">{{ str($report->activity_description)->limit(140) }}</p></article>
            @empty<p class="p-5 text-sm text-slate-500">Belum ada laporan hari ini.</p>@endforelse
        </div></section>
    </div>
</x-admin-layout>
